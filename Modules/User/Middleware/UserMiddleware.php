<?php

namespace Modules\User\Presentation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Logique spécifique au module User
        // Exemple: Vérifier si l'utilisateur a accès aux fonctionnalités User

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Vous pouvez ajouter des vérifications spécifiques ici
        // Par exemple, vérifier si l'utilisateur est activé, a les permissions, etc.

        return $next($request);
    }
}
