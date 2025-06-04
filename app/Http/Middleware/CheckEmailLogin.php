<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmailLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (auth()->user()->last_login_type !== 'email') {
            return redirect()->route('dashboard')->with('error', 'Acesso negado. Esta funcionalidade está disponível apenas para usuários que fizeram login com email e senha.');
        }
        
        return $next($request);
    }
}
