<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class RefreshToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Passez d'abord la requête au prochain middleware/contrôleur
        $response = $next($request);

        try {
            // Vérifiez si un token est présent
            if ($token = JWTAuth::getToken()) {
                // Essayez de rafraîchir le token silencieusement
                $newToken = JWTAuth::refresh($token, false);

                if ($newToken && $newToken !== $token) {
                    // Ajoutez le nouveau token aux headers
                    $response->headers->set('Authorization', 'Bearer ' . $newToken);
                    $response->headers->set('Access-Control-Expose-Headers', 'Authorization');

                    // Optionnel: Ajoutez aussi dans le body
                    $this->addTokenToResponse($response, $newToken);
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, on ne fait rien et on retourne la réponse originale
            // Le token reste valide ou l'utilisateur devra se reconnecter
        }

        return $response;
    }

    /**
     * Ajoute le token à la réponse JSON si possible.
     */
    protected function addTokenToResponse(Response $response, string $newToken): void
    {
        $content = $response->getContent();

        try {
            $data = json_decode($content, true);

            if (is_array($data)) {
                $data['token'] = $newToken;
                $response->setContent(json_encode($data));
            }
        } catch (\Exception $e) {
            // Ne rien faire si on ne peut pas parser le JSON
        }
    }
}