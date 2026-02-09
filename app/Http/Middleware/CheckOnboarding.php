<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Permetti accesso alle route di onboarding e aggiornamento profilo
        // Nota: Le route di onboarding sono già fuori dal middleware, ma questo è un controllo aggiuntivo
        $allowedPaths = [
            'user/get-info-user',
            'user/update-profile',
            'user/onboarding-status',
            'user/complete-onboarding',
            'locations',
        ];

        $currentPath = $request->path(); // Restituisce il path senza il prefisso "api/"
        $isAllowed = false;
        
        // Controlla se il path corrente corrisponde a una delle route permesse
        foreach ($allowedPaths as $allowedPath) {
            // Controlla match esatto o se il path inizia con il percorso permesso
            if ($currentPath === $allowedPath || str_starts_with($currentPath, $allowedPath . '/')) {
                $isAllowed = true;
                break;
            }
        }

        // Se l'onboarding non è completo e la route non è permessa, blocca
        if (!$user->onboarding_completed && !$isAllowed) {
            return response()->json([
                'error' => 'Onboarding non completato',
                'onboarding_required' => true,
                'message' => 'Completa il profilo per accedere a questa funzionalità',
            ], 403);
        }

        return $next($request);
    }
}
