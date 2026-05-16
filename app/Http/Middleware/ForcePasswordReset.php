<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && 
            $request->user()->requires_password_reset && 
            !$request->is('force-reset-password') && 
            !$request->is('logout')) {
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Password reset required'], 403);
            }

            return redirect()->route('password.force-reset');
        }

        return $next($request);
    }
}
