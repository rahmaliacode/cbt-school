<?php

namespace App\Http\Middleware;

use Closure;

class AdminOnly
{
    public function handle($request, Closure $next)
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }abort_unless(auth()->user()->is_admin, 403);

        return $next($request);
    }
}
