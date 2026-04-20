<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeApiErrorDetails
{
    /**
     * Hide internal exception details from API responses in production.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('app.debug')) {
            return $response;
        }

        if (! $request->is('api/*')) {
            return $response;
        }

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $data = $response->getData(true);

        if (is_array($data) && array_key_exists('error', $data)) {
            $data['error'] = 'Une erreur interne est survenue.';
            $response->setData($data);
        }

        return $response;
    }
}
