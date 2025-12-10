<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Map role names → database IDs
        $roleMap = [
            'superadmin'  => 1,
            'schooladmin' => 2,
            'teacher'     => 3,
        ];

        // Convert incoming names to IDs
        $allowedRoleIds = array_map(function ($role) use ($roleMap) {
            return $roleMap[$role] ?? null;
        }, $roles);

        if (!in_array($user->role_id, $allowedRoleIds)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
