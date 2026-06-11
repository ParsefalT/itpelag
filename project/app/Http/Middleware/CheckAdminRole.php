<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->hasRole("admin")) {
            abort(403, "Access denied.");
        }
        return $next($request);
    }
}
