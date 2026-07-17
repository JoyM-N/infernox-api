<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────
    // POST /api/auth/login
    // Called by: Next.js frontend login page
    // Returns: Bearer token + user info
    // ─────────────────────────────────────────────
    public function login(LoginRequest $request): JsonResponse
    {
        // attempt() checks email + password against the database
        // returns false if credentials are wrong
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if account is active
        // Deactivated operators cannot log in
        if (! $user->isActive()) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        // Record when this user last logged in
        $user->recordLogin();

        // Create a new API token for this session
        // The token name includes the device/app for audit purposes
        $token = $user->createToken(
            name: 'operator-session',
            abilities: ['*'],  // operators can do everything their role allows
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'last_login'  => $user->last_login_at?->toIso8601String(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/logout
    // Called by: frontend when user clicks logout
    // Revokes the current token
    // ─────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        // Check if there's an actual token to delete
        // actingAs() in tests doesn't create a real token
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
    
        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/auth/me
    // Called by: frontend to get current user info
    // Returns: current logged in user details
    // ─────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'is_active'   => $user->is_active,
            'last_login'  => $user->last_login_at?->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/register
    // Called by: super_admin to create new operator accounts
    // NOT a public endpoint — requires super_admin role
    // ─────────────────────────────────────────────
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->getRoleNames()->first(),
            ],
        ], 201);
    }
}