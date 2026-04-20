<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        // Vérifier que l'utilisateur a l'un des rôles autorisés
        if (! in_array($request->user()->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle insuffisant.',
                'required_roles' => $roles,
                'user_role' => $request->user()->role,
            ], 403);
        }

        return $next($request);
    }
}
