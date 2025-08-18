<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.debug')) {
            $this->logRequest($request);
        }

        return $next($request);
    }

    private function logRequest(Request $request): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Filter sensitive headers
        $logData['headers'] = $this->filterSensitiveHeaders($logData['headers']);

        Log::debug('HTTP Request', $logData);
    }

    private function filterSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }

        return $headers;
    }
}