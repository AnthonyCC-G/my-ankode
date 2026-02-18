<?php

/**
 * CSRFVALIDATIONSUBSCRIBER.PHP - Validation automatique des tokens CSRF pour les API
 * 
 * Responsabilités :
 * - Intercepter toutes les requêtes HTTP via Event Subscriber Symfony
 * - Valider automatiquement le token CSRF pour les routes API modifiantes
 * - Bloquer les requêtes sans token ou avec token invalide (HTTP 400)
 * - Exclure certaines routes de la validation (ex: /api/csrf-token)
 * 
 * Architecture :
 * - Event Subscriber Symfony (KernelEvents::REQUEST avec priorité 10)
 * - Vérifie uniquement les routes commençant par /api/
 * - Vérifie uniquement les méthodes POST, PUT, PATCH, DELETE
 * - Token attendu dans le header HTTP : X-CSRF-Token
 * - Token généré via /api/csrf-token (identifiant 'api')
 * 
 * Sécurité :
 * - Protection CSRF globale pour toutes les API modifiantes
 * - Défense en profondeur : header X-CSRF-Token requis
 * - Validation côté serveur avec CsrfTokenManager Symfony
 * - Routes exclues : /api/csrf-token (pour récupérer le token)
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Valide ou non le token CSRF pour toutes les routes API
 * qui modifient des données (POST, PUT, PATCH, DELETE)
 */
class CsrfValidationSubscriber implements EventSubscriberInterface
{
    // ===== 1. CONSTANTES DE CONFIGURATION =====
    
    private const API_PREFIX = '/api/';
    private const METHODS_REQUIRING_CSRF = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const EXCLUDED_ROUTES = [
        '/api/csrf-token',
    ];

    // ===== 2. INJECTION DE DÉPENDANCE - CSRF TOKEN MANAGER =====
    
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    // ===== 3. ENREGISTREMENT DE L'EVENT SUBSCRIBER =====
    
    public static function getSubscribedEvents(): array
    {
        // Écoute l'événement REQUEST avec priorité 10 (avant la plupart des listeners)
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    // ===== 4. LOGIQUE DE VALIDATION CSRF =====
    
    public function onKernelRequest(RequestEvent $event): void
    {
        // 4a. Vérifier que c'est la requête principale (pas une sous-requête)
        if (!$event->isMainRequest()) {
            return;
        }

        // 4b. Extraction des informations de la requête HTTP
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // 4c. Vérifier si c'est une route API (commence par /api/)
        if (!str_starts_with($path, self::API_PREFIX)) {
            return;
        }

        // 4d. Vérifier si la route est exclue de la validation CSRF
        if (in_array($path, self::EXCLUDED_ROUTES)) {
            return;
        }

        // 4e. Vérifier si la méthode HTTP nécessite une validation CSRF
        // GET et OPTIONS ne modifient pas de données donc pas de CSRF requis
        if (!in_array($method, self::METHODS_REQUIRING_CSRF)) {
            return;
        }

        // 4f. Récupération du token CSRF depuis le header HTTP
        $token = $request->headers->get('X-CSRF-Token');

        // 4g. Validation : token présent
        if (!$token) {
            throw new BadRequestHttpException('Token CSRF manquant');
        }

        // 4h. Validation : token valide selon le CsrfTokenManager
        // new CsrfToken('api', $token) : identifiant 'api' + valeur du token
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('api', $token))) {
            throw new BadRequestHttpException('Token CSRF invalide');
        }
    }
}