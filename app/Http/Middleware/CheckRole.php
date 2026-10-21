<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
         // Check if the authenticated user has the required role
        if ($request->user() && $request->user()->hasRole($role)) {
            return $next($request);
        }

        // If the user doesn't have the required role, you can redirect or return an error response
        // For example, redirect to the home page with a flash message
        return redirect()->route('home')->with('error', 'Unauthorized. You do not have the required role.');
    }
}
