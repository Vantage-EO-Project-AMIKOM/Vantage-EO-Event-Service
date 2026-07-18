<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithAuthService
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(5)
                ->get(rtrim(config('services.auth.url'), '/').'/me');
        } catch (\Throwable) {
            return response()->json(['message' => 'Authentication service unavailable.'], 503);
        }

        if (!$response->successful()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $response->json();
        if (!isset($user['id'], $user['role']) || !in_array($user['role'], ['user', 'admin'], true)) {
            return response()->json(['message' => 'This account cannot perform this action.'], 403);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
