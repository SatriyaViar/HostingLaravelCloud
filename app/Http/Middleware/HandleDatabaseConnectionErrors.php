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
        try {
            return $next($request);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a prepared statement error from connection pooling
            if ($this->isPreparedStatementError($e)) {
                Log::warning('Prepared statement error detected, reconnecting...', [
                    'error' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                ]);

                // Force disconnect and reconnect
                DB::purge('pgsql');
                DB::reconnect('pgsql');

                // Retry the request once
                try {
                    return $next($request);
                } catch (\Exception $retryException) {
                    Log::error('Retry after reconnect failed', [
                        'error' => $retryException->getMessage(),
                    ]);
                    throw $retryException;
                }
            }

            // Re-throw if not a prepared statement error
            throw $e;
        }
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
