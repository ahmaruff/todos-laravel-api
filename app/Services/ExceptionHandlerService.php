<?php
namespace App\Services;

use App\Commands\ResponseJsonCommand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;

class ExceptionHandlerService
{
    protected $exceptionMap;
    public function __construct(private LogService $logService)
    {
        $this->initializeExceptionMap();
    }

    public function handle(Throwable $e)
    {
        // Let Laravel handle web requests with default behavior
        if (!$this->shouldReturnJsonResponse()) {
            return null; // Let Laravel's default handler take over
        }

        $exceptionClass = get_class($e);

        // Check if we have a specific handler for this exception
        if (isset($this->exceptionMap[$exceptionClass])) {
            return $this->renderMappedException($e, $this->exceptionMap[$exceptionClass]);
        }

        $rawCode = (int) $e->getCode();
        $code = ($rawCode >= 100 && $rawCode < 600) ? $rawCode : Response::HTTP_INTERNAL_SERVER_ERROR;

        $status = $code >= 400 && $code < 500
            ? ResponseJsonCommand::FAIL
            : ResponseJsonCommand::ERROR;

        $default = [
            'status' => $status,
            'code' => $code,
            'message' => $e->getMessage(),
            'log_level' => LogService::LEVEL_ERROR,
            'log_task' => 'default_exception_handling',
            'log_status' => $status,
        ];

        return $this->renderMappedException($e, $default);
    }

    private function shouldReturnJsonResponse(): bool
    {
        $request = request();

        return $request->is('api/*')
            || $request->wantsJson()
            || $request->expectsJson()
            || $request->ajax();
    }

    private function renderMappedException(Throwable $e, array $config)
    {
        $data = [
            'error' => $e->getMessage()
        ];

        if($e instanceof ValidationException) {
            $data = [
                'error' => $e->validator->errors(),
            ];
        }

        if($e instanceof ModelNotFoundException){
            $data = [
                'error' => "Entry for {$e->getModel()} not found"
            ];
        }

        if(!app()->isProduction()) {
            $data['error_detail'] = $this->getErrorDetail($e);
        }

        // Log the exception
        $this->saveLog(
            $e,
            $config['log_level'],
            $config['log_task'],
            $config['log_status'],
            $config['code']
        );

        return ResponseJsonCommand::render($config['log_status'], $config['code'], $config['message'], $data);
    }

    private function saveLog(Throwable $e, string $level = "error", string $task = 'exception_handling', $status = "error", $code = null): void
    {
        if ($code === null) {
            $code = $e->getCode();
        }

        $this->logService
            ->level($level)
            ->status($status)
            ->code($code)
            ->task($task)
            ->message($e->getMessage())
            ->error($e);

        if(request()) {
            $this->logService->request(request());
        }

        $this->logService->save();
    }

    private function initializeExceptionMap(): void
    {
        $this->exceptionMap = [
            AccessDeniedHttpException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => "Whoops! Looks like you're not authorized to view this content. Please check with the admin if you think this is a mistake",
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'authorization_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            AuthenticationException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Authentication required',
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'authentication_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            AuthorizationException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => "Whoops! You don't have permission to access this resource",
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'authorization_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            NotFoundHttpException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "Whoops! Resource Unavailable: The API endpoint you're attempting to access doesn't exist",
                'log_level' => LogService::LEVEL_WARNING,
                'log_task' => 'not_found_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            QueryException::class => [
                'status' => ResponseJsonCommand::ERROR,
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => "Whoops! It looks like there was a hiccup while executing your request. The database query encountered an error",
                'log_level' => LogService::LEVEL_CRITICAL,
                'log_task' => 'sql_query_exception_handling',
                'log_status' => LogService::STATUS_ERROR,
            ],

            PDOException::class => [
                'status' => ResponseJsonCommand::ERROR,
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => "Whoops! PDOException. Check database settings and query syntax",
                'log_level' => LogService::LEVEL_CRITICAL,
                'log_task' => 'database_exception_handling',
                'log_status' => LogService::STATUS_ERROR,
            ],

            MethodNotAllowedHttpException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                'message' => "Whoops! It seems you're using the wrong method here. This action isn't supported for this resource",
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'http_method_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            BadRequestException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Bad request',
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'bad_request_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            ModelNotFoundException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "Whoops! It seems we couldn't find what you were looking for. The requested data couldn't be located",
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'model_not_found_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            ValidationException::class => [
                'status' => ResponseJsonCommand::FAIL,
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => "Whoops! It seems the data you entered didn't pass our validation checks. There might be some errors or missing fields",
                'log_level' => LogService::LEVEL_INFO,
                'log_task' => 'validation_exception_handling',
                'log_status' => LogService::STATUS_FAIL,
            ],

            // Default fallback
            'default' => [
                'status' => ResponseJsonCommand::ERROR,
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An unexpected error occurred',
                'log_level' => 'error',
                'log_task' => 'default_exception_handling',
                'log_status' => LogService::STATUS_ERROR,
            ]
        ];
    }

    private function getErrorDetail(Throwable $th)
    {
        return [
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'type' => class_basename($th),
            'class' => get_class($th),
            'trace' => $th->getTraceAsString()
        ];
    }
}
