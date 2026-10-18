<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPanelMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->canDo('access_admin_panel')) {
            abort(403, 'Zugriff auf das Admin-Panel verweigert.');
        }

        return $next($request);
    }
}
