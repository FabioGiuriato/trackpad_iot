<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserButtonMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:50', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
        ]);

        UserButtonMapping::ensureDefaultsFor($user);

        return response()->json($this->tokenResponse($user, $data['device_name'] ?? 'android'), 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Credenziali non valide.',
            ], 422);
        }

        UserButtonMapping::ensureDefaultsFor($user);

        return response()->json($this->tokenResponse($user, $data['device_name'] ?? 'android'));
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json([
            'message' => 'Logout effettuato correttamente.',
        ]);
    }

    private function tokenResponse(User $user, string $deviceName): array
    {
        $issued = ApiToken::issueFor($user, $deviceName);

        return [
            'token_type' => 'Bearer',
            'access_token' => $issued['plain_token'],
            'expires_at' => $issued['api_token']->expires_at?->toISOString(),
            'user' => $this->userPayload($user),
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }
}
