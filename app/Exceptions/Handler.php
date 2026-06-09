<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if($request->is('api/*'))
        {
            if ($exception instanceof MethodNotAllowedHttpException)
            {
                return response()->json( [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'status' => '405',
                ], 405);
            }
            
            if (!$request->wantsJson()) {
                
                return response()->json( [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'status' => '400',
                ], 400);
            }
        }
        else{
            if (app()->environment('production')) {
                return response()->view('errors.custom', ['exception' => $exception], 500);
            }
            // Fallback to the default error handling
            return parent::render($request, $exception);
        }
        
    }

    public function report(Throwable $exception)
    {
        \Log::error($exception);
        //return parent::render($exception);
    }

}
