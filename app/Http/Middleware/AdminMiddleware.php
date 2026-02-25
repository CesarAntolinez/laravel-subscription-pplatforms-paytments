<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'Acceso denegado. Se requiere rol administrativo.');
        }

        return $next($request);
    }
}
