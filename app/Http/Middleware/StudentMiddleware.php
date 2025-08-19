<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login')->with('error', 'Acesso não autorizado.');
        }

        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Administradores não precisam bater ponto.');
        }

        return $next($request);
    }
}