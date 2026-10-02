<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:255'],
            'block' => ['required', 'string', 'max:20'],
            'house_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'],
            'block' => $validated['block'],
            'house_number' => $validated['house_number'],
            'password' => $validated['password'],
            'role' => 'buyer',
        ]);
        $user->refresh();

        AuditLogger::log('USER_REGISTERED', 'User', $user->id, ['role' => $user->role, 'source' => 'api']);

        return $this->issueTokenResponse($user, $validated['device_name'] ?? null, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()
            ->where($field, $credentials['login'])
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Login atau kata sandi tidak cocok.']]);
        }

        AuditLogger::log('USER_LOGIN', 'User', $user->id, ['role' => $user->role, 'source' => 'api']);

        return $this->issueTokenResponse($user, $credentials['device_name'] ?? null);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('sellerProfile'));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        $token?->delete();

        return response()->json(['message' => 'Token berhasil dicabut.']);
    }

    private function issueTokenResponse(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken($deviceName ?: 'api-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toIso8601String(),
                'user' => (new UserResource($user->load('sellerProfile')))->resolve(),
            ],
        ], $status);
    }
}
