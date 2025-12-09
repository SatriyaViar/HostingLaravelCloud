<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleDatabaseConnectionErrors
{
    /**
     * Handle database connection errors and force reconnect
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $next($request);
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a prepared statement error from connection pooling
                if ($this->isPreparedStatementError($e)) {
                    $attempt++;
                    
                    Log::warning('Prepared statement error detected, reconnecting...', [
                        'error' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'attempt' => $attempt,
                    ]);

                    // Force disconnect and reconnect
                    try {
                        DB::purge('pgsql');
                        // Clear all connections
                        DB::disconnect('pgsql');
                    } catch (\Exception $purgeException) {
                        Log::warning('Error during purge', [
                            'error' => $purgeException->getMessage(),
                        ]);
                    }

                    // Wait a bit before reconnecting
                    usleep(100000); // 0.1 second

                    // Reconnect
                    try {
                        DB::reconnect('pgsql');
                        
                        // Verify connection is working
                        DB::connection('pgsql')->getPdo();
                    } catch (\Exception $reconnectException) {
                        Log::error('Reconnect failed', [
                            'error' => $reconnectException->getMessage(),
                        ]);
                        
                        if ($attempt >= $maxRetries) {
                            throw $e; // Throw original error if max retries reached
                        }
                        continue; // Try again
                    }

                    // If we've exhausted retries, throw the original error
                    if ($attempt >= $maxRetries) {
                        throw $e;
                    }
                    
                    // Continue to retry
                    continue;
                }

                // Re-throw if not a prepared statement error
                throw $e;
            }
        }

        // Should never reach here, but just in case
        throw new \RuntimeException('Max retries exceeded');
    }

    /**
     * Check if exception is related to prepared statement issues
     */
    private function isPreparedStatementError(\Illuminate\Database\QueryException $e): bool
    {
        $message = $e->getMessage();
        
        return str_contains($message, 'prepared statement') ||
               str_contains($message, 'pdo_stmt_') ||
               str_contains($message, 'bind message supplies') ||
               str_contains($message, 'SQLSTATE[26000]') ||
               str_contains($message, 'SQLSTATE[08P01]');
    }
}
