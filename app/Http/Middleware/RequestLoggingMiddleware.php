<?php

namespace App\Http\Middleware;

use App\Services\LogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class RequestLoggingMiddleware
{
    public function __construct(private LogService $logService) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        app()->instance('request_id', $requestId);
        $request->headers->set('X-Request-ID', $requestId);

        $shouldLog = $this->shouldLogRequest($request);

        if (!$shouldLog) {
            return $next($request);
        }

        $response = $next($request);

        $this->logRequestCycle($request, $response);

        return $response;
    }

    private function shouldLogRequest(Request $request): bool
    {

        // Always log API reques// Skip logging for specific URLs
        if ($request->is('api') || $request->is('api/logs') || $request->is('api/logs/*')) {
            return false;
        }

        if ($request->is('api/*')) {
            return true;
        }

        // Log important web requests
        if ($this->isImportantWebRequest($request)) {
            return true;
        }

        return false;
    }

    private function isImportantWebRequest(Request $request): bool
    {
        // Skip routine GET requests to reduce noise
        if ($request->method() === 'GET') {
            return false;
        }

        // Skip static assets
        if ($this->isStaticAsset($request)) {
            return false;
        }

        $match = [
            "login*",
            "register*",
            "logout*"
        ];

        if ($request->is($match)) {
            return true;
        }

        // Log form submissions
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }

        // Log errors (this will be handled in logRequestCycle)
        // We'll check the response status there

        return true;
    }

    private function isStaticAsset(Request $request): bool
    {
        return $request->is('*.css') || $request->is('*.js') ||
            $request->is('*.png') || $request->is('*.jpg') ||
            $request->is('*.gif') || $request->is('*.ico') ||
            $request->is('*.svg') || $request->is('*.woff*');
    }

    private function logRequestCycle(Request $request, Response $response): void
    {
        $statusCode = $response->getStatusCode();
        $isApiRequest = $request->is('api/*');

        // Build log entry using LogService
        $logEntry = $this->logService
            ->detectContext($request)
            ->task($isApiRequest ? 'api_request_cycle' : 'web_request_cycle')
            ->code($statusCode)
            ->message($this->buildMessage($request, $response));

        // Set appropriate level and status
        $level = LogService::LEVEL_INFO;
        $status = LogService::STATUS_SUCCESS;

        if ($statusCode >= 500) {
            $level = LogService::LEVEL_ERROR;
            $status = LogService::STATUS_ERROR;

        } elseif ($statusCode >= 400) {
            $level = LogService::LEVEL_WARNING;
            $status = LogService::STATUS_FAIL;
        }

        $logEntry->level($level)->status($status);


        // Add response data conditionally (different rules for web vs API)
        if ($this->shouldLogResponseData($request, $response)) {
            $logEntry->response($this->getResponseData($response, $isApiRequest));
        }

        // Save the log
        $logEntry->save();
    }

    private function buildMessage(Request $request, Response $response): string
    {
        return "{$request->method()} {$request->path()} - {$response->getStatusCode()}";
    }

    private function shouldLogResponseData(Request $request, Response $response): bool
    {
        $statusCode = $response->getStatusCode();
        $isApiRequest = $request->is('api/*') || $request->expectsJson();

        if($request->is('api/logs') || $request->is('api/logs/*')) {
            return false;
        }

        // Always log response for errors
        if ($statusCode >= 400) {
            return true;
        }

        if ($isApiRequest) {
            // For API: Log response for mutations to see what was created/updated
            return in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH']);
        } else {
            // For Web: Only log response for forms and errors
            return in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH']) &&
                   !$this->isStaticAsset($request);
        }
    }

     private function getResponseData(Response $response, bool $isApiRequest = true): array
    {
        $content = $response->getContent();

        if (!$isApiRequest) {
            // For web requests, just log basic info about HTML responses
            if (str_contains($response->headers->get('content-type', ''), 'text/html')) {
                return [
                    'type' => 'html',
                    'size' => strlen($content),
                    'title' => $this->extractPageTitle($content),
                ];
            }
        }

        // For API requests or non-HTML responses, use existing logic
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return ['raw_content' => substr($content, 0, 200)];
        }

        // Keep important fields, limit others (existing logic)
        $result = [];
        $importantKeys = ['status', 'code', 'message', 'error', 'errors'];

        // Always keep important keys
        foreach ($importantKeys as $key) {
            if (isset($decoded[$key])) {
                $result[$key] = $decoded[$key];
            }
        }

        // Add first 3 other keys
        $otherKeys = array_diff(array_keys($decoded), $importantKeys);
        $processed = 0;

        foreach ($otherKeys as $key) {
            if ($processed >= 3) {
                $result['_more_fields'] = count($otherKeys) - 3;
                break;
            }

            $value = $decoded[$key];

            // Simple truncation for arrays
            if (is_array($value) && count($value) > 3) {
                $result[$key] = array_slice($value, 0, 3);
                $result[$key . '_count'] = count($value);
            } else {
                $result[$key] = $value;
            }

            $processed++;
        }

        return $result;
    }

    private function extractPageTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        return null;
    }
}
