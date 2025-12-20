<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is authenticated but account is inactive, revoke all tokens and logout
        if ($user && !$user->is_active) {
            // Delete all tokens for this user
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => 'تم تعطيل حسابك. يرجى تسجيل الدخول مرة أخرى.',
            ], 401);
        }

        return $next($request);
    }
}

