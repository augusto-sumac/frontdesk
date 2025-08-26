<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Se não estiver autenticado, deixa o middleware de auth cuidar
        if (!$user) {
            return $next($request);
        }

        // Permitir acesso ao perfil mesmo sem tenant configurado
        if ($request->routeIs('profile.*')) {
            return $next($request);
        }

        // Permitir acesso à área administrativa para admins mesmo sem tenant
        if ($request->routeIs('admin.*') && $user->role === 'admin') {
            return $next($request);
        }

        if (empty($user->property_manager_code)) {
            return redirect()->route('profile.index')
                ->with('warning', 'Configure seu Property Manager Code (tenant) para acessar as funcionalidades.');
        }

        return $next($request);
    }
} 