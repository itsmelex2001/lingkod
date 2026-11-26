<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $role = strtolower(trim((string) ($user->role ?? '')));
            if (! in_array($role, ['admin', 'superadmin'], true)) {
                return response()->json(['message' => 'Forbidden. System Administrator access required.'], 403);
            }
            return $next($request);
        }

        // Optional same-origin PHP session bridge for XAMPP admin-dashboard.php
        if (!app()->runningUnitTests() && session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
            @session_start();
        }
        $sessionRole = strtolower((string) ($_SESSION['role'] ?? ''));
        if (!empty($_SESSION['user']) && in_array($sessionRole, ['admin', 'superadmin'], true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
