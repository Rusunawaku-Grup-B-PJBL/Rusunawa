<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menyeragamkan response sukses JSON menjadi {status, message, data}. */
class ApiResponseEnvelope
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 300 || $code === 204) {
            return $response;
        }

        $payload = $response->getData(true);
        if (! is_array($payload) || array_is_list($payload)) {
            return $response;
        }

        $payload = [
            'status' => 'success',
            'message' => $payload['message'] ?? 'Berhasil.',
        ] + $payload;

        if (! array_key_exists('data', $payload)) {
            $payload['data'] = null;
        }

        $response->setData($payload);

        return $response;
    }
}