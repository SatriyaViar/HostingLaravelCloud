<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response for API requests.
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            $debug = env('APP_DEBUG', false);
            
            return response()->json([
                'error' => true,
                'message' => $debug ? $e->getMessage() : 'Server Error',
                'file' => $debug ? $e->getFile() : null,
                'line' => $debug ? $e->getLine() : null,
                'trace' => $debug ? explode("\n", $e->getTraceAsString()) : null,
                'class' => $debug ? get_class($e) : null,
            ], 500);
        }

        return parent::render($request, $e);
    }
}
