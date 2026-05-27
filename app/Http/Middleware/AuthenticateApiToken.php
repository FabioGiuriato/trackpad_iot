<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json([
                'message' => 'Token mancante.',
            ], 401);
        }

        $apiToken = ApiToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiToken || !$apiToken->user) {
            return response()->json([
                'message' => 'Token non valido o scaduto.',
            ], 401);
        }

        $apiToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::setUser($apiToken->user);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
