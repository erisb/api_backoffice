<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            $exception = new NotFoundHttpException('HTTP_NOT_FOUND', $exception);
            return response()->json(['statusCode' => Response::HTTP_NOT_FOUND,'message' => $exception->getMessage()]);
        } 
        else if ($exception instanceof MethodNotAllowedHttpException) {
            $exception = new MethodNotAllowedHttpException([],'HTTP_METHOD_NOT_ALLOWED', $exception);
            return response()->json(['statusCode' => Response::HTTP_METHOD_NOT_ALLOWED,'message' => $exception->getMessage()]);
        } else if ($exception instanceof HttpResponseException) {
            $exception = new HttpResponseException(Response::HTTP_INTERNAL_SERVER_ERROR,'HTTP_INTERNAL_SERVER_ERROR');
            return response()->json(['statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,'message' => $exception->getMessage()]);
        }else if ($exception instanceof AuthorizationException) {
            $exception = new AuthorizationException('HTTP_FORBIDDEN', Response::HTTP_FORBIDDEN);
            return response()->json(['statusCode' => Response::HTTP_FORBIDDEN,'message' => $exception->getMessage()]);
        }else if ($exception instanceof  \Dotenv\Exception\ValidationException && $exception->getResponse()) {
            $exception = new   \Dotenv\Exception\ValidationException('HTTP_BAD_REQUEST', Response::HTTP_BAD_REQUEST, $exception);
            return response()->json(['statusCode' => Response::HTTP_BAD_REQUEST,'message' => $exception->getMessage()]);
        }

        return parent::render($request, $exception);
    }
}
