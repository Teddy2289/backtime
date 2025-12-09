<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends BaseAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Si aucun guard n'est spécifié, utilisez 'api' par défaut
        if (empty($guards)) {
            $guards = ['api'];
        }

        try {
            // Tente d'authentifier la requête en utilisant les guards spécifiés
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            // Pour les requêtes API, retourner une réponse JSON au lieu de rediriger
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'error' => 'AUTH_001'
                ], 401);
            }

            throw $e;
        }

        return $next($request);
    }

    /**
     * Rediriger l'utilisateur lorsqu'il n'est pas authentifié.
     * Pour une API, cette méthode doit retourner null pour ne pas rediriger.
     */
    protected function redirectTo(Request $request): ?string
    {
        // On retourne null pour éviter toute tentative de redirection web.
        return null;
    }
}