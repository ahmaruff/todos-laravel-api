<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class LogService
{
    protected $request;
    protected $logData;
    protected $startTime;
    protected $agentService;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';
    public const STATUS_ERROR = 'error';

    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    public function __construct(AgentService $agentService)
    {
        $requestId = App::bound('request_id') ? App::make('request_id') : Str::uuid();

        $this->logData = [
            'meta' => [
                'request_id' => $requestId,
                'status' => self::STATUS_SUCCESS,
                'level' => self::LEVEL_INFO,
                'code' => 200,
                'task' => 'process_data',
                'message' => 'Data processing completed',
                'duration_ms' => 0,
            ],

            'request' => [
                'method' => null,
                'url' => null,
                'ip' => null,
                'data' => null,
                'agent' => [
                    'browser' => null,
                    'platform' => null,
                    'device' => null,
                ]
            ],

            'user' => [
                'id' => null,
                'email' => null,
            ],

            'response' => [
                'data' => null,
            ],

            'error' => [
                'message' => null,
                'file' => null,
                'line' => null,
                'trace' => null,
                'type' => null,
                'class' => null,
            ],

            'timestamp' => [
                'utc' => Carbon::now()->utc()->toIso8601ZuluString(),
                'local' => Carbon::now()->setTimezone('Asia/Jakarta')->toIso8601String(),
            ],

            'app_version' => config('app.version', null),
            'app_env' => config('app.env', null),
            'app_service' => config('app.service', null),
        ];

        $this->agentService = $agentService;
    }

    public function status(string $status): self
    {
        $this->logData['meta']['status'] = strtolower($status);
        return $this;
    }

    public function level(string $level): self
    {
        $this->logData['meta']['level'] = strtolower($level);
        return $this;
    }

    public function code(int $code): self
    {
        $this->logData['meta']['code'] = $code;
        return $this;
    }

    public function message(string $message): self
    {
        $this->logData['meta']['message'] = $message;
        return $this;
    }

    public function task(?string $task = null): self
    {
        $this->logData['meta']['task'] = $task ?? $this->getDefaultTaskName();
        return $this;
    }

    public function cli(): self
    {
        $this->startTime = microtime(true);

        $this->logData['request'] = [
            'method' => 'CLI',
            'url' => implode(' ', $_SERVER['argv'] ?? []),
            'ip' => gethostbyname(gethostname()),
            'data' => null,
            'agent' => [
                'browser' => php_uname('s'),
                'platform' => PHP_OS,
                'device' => 'cli',
            ],
        ];

        $user = Auth::user();
        if ($user) {
            $this->logData['user']['id'] = $user->id;
            $this->logData['user']['email'] = $user->email;
        }

        return $this;
    }

    public function request(Request $request): self
    {
        $this->request = $request;
        $this->startTime = microtime(true);

        $this->logData['request']['method'] = $request->method();
        $this->logData['request']['url'] = $request->fullUrl();
        $this->logData['request']['ip'] = $request->ip();

        // Only include request data for mutations or errors
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $this->logData['request']['data'] = $this->sanitizeRequestData($request->all());
        }

        if ($user = Auth::user()) {
            $this->logData['user']['id'] = $user->id;
            $this->logData['user']['email'] = $user->email;
        }

        return $this;
    }

    public function response($response): self
    {
        $this->logData['response']['data'] = $response;
        return $this;
    }

    public function error(\Throwable $error): self
    {
        $this->logData['error']['message'] = $error->getMessage();
        $this->logData['error']['file'] = $error->getFile();
        $this->logData['error']['line'] = $error->getLine();
        $this->logData['error']['type'] = class_basename($error);
        $this->logData['error']['class'] = get_class($error);

        // Only include trace in development
        if (!app()->isProduction()) {
            $this->logData['error']['trace'] = $error->getTraceAsString();
        }

        return $this;
    }

    public function data(array $data): self
    {
        // Allow adding custom data at root level
        foreach ($data as $key => $value) {
            // Don't overwrite existing structure
            if (!in_array($key, ['meta', 'request', 'user', 'response', 'error', 'timestamp'])) {
                $this->logData[$key] = $value;
            }
        }
        return $this;
    }

    public function start(): self
    {
        $this->startTime = microtime(true);
        return $this;
    }

    public function save(): bool
    {
        // Calculate duration
        if ($this->startTime) {
            $this->logData['meta']['duration_ms'] = (int) round((microtime(true) - $this->startTime) * 1000, 0);
        }

        // Include user agent data if available
        if ($this->request) {
            $agent = $this->agentService->getAgent($this->request);
            $this->logData['request']['agent'] = $agent;
        } elseif (app()->runningInConsole()) {
            $this->cli();
        }

        // Clean up empty nested objects
        $this->logData = $this->removeEmptyObjects($this->logData);

        if(empty($this->logData['meta']['task'])) {
            $this->logData['meta']['task'] = $this->getDefaultTaskName();
        }

        // Log using the level
        $level = $this->logData['meta']['level'] ?? 'info';

        if (method_exists(Log::channel('activity'), $level)) {
            Log::channel('activity')->$level($this->logData['meta']['message'], $this->logData);
        } else {
            Log::channel('activity')->debug('[Invalid level fallback] ' . $this->logData['meta']['message'], $this->logData);
        }

        return true;
    }

    private function sanitizeRequestData(array $data): array
    {
        // Remove sensitive fields
        unset($data['password'], $data['password_confirmation'], $data['token']);
        return $data;
    }

    private function removeEmptyObjects(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Remove null values from nested arrays
                $cleaned = array_filter($value, function($v) {
                    return $v !== null;
                });

                // If nested array is empty after cleaning, remove it
                if (empty($cleaned)) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $cleaned;
                }
            } elseif ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    private function getDefaultTaskName(): ?string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // Skip LogService itself and the direct caller
        if (!isset($backtrace[2])) {
            return null;
        }

        $caller = $backtrace[2];

        if (isset($caller['class'])) {
            $class = class_basename($caller['class']);
            $method = $caller['function'] ?? 'unknown';
            return "{$class}_{$method}";
        }

        return 'unknown_task';
    }

    public function detectContext($request = null): self
    {
        if ($request && $request instanceof Request) {
            return $this->request($request);
        }

        if (app()->runningInConsole()) {
            return $this->cli();
        }

        return $this;
    }
}
