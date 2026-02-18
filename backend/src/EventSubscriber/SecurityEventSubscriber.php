<?php

/**
 * SECURITYEVENTSUBSCRIBER.PHP - Logging des événements de sécurité (connexions)
 * 
 * Responsabilités :
 * - Logger toutes les connexions réussies avec informations utilisateur
 * - Logger tous les échecs de connexion pour détection d'intrusion
 * - Enregistrer IP, User-Agent, email pour analyse de sécurité
 * - Utiliser les niveaux de log appropriés (info pour succès, warning pour échec)
 * 
 * Architecture :
 * - Event Subscriber Symfony (LoginSuccessEvent, LoginFailureEvent)
 * - Logs écrits via LoggerInterface Symfony (monolog)
 * - Informations contextuelles : IP, User-Agent, email/user, raison d'échec
 * 
 * Sécurité :
 * - Détection des tentatives d'intrusion via logs d'échecs
 * - Traçabilité complète des connexions pour audit
 * - Données sensibles loggées : email, IP (conformité RGPD à vérifier)
 */

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Logger les connexions réussies et échouées pour la sécurité
 * Permet de détecter les tentatives d'intrusion
 */
class SecurityEventSubscriber implements EventSubscriberInterface
{
    // ===== 1. INJECTION DE DÉPENDANCE - LOGGER =====
    
    public function __construct(
        private LoggerInterface $logger
    ) {}

    // ===== 2. ENREGISTREMENT DE L'EVENT SUBSCRIBER =====
    
    public static function getSubscribedEvents(): array
    {
        // Écoute des événements de sécurité Symfony
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    // ===== 3. LOGGING DES CONNEXIONS RÉUSSIES =====
    
    /**
     * Logger les connexions réussies avec IP et User-Agent
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        // 3a. Récupération de l'utilisateur connecté
        $user = $event->getUser();
        
        // 3b. Récupération de la requête HTTP pour contexte
        $request = $event->getRequest();

        // 3c. Log niveau INFO avec contexte complet
        $this->logger->info('Connexion réussie', [
            'user' => $user->getUserIdentifier(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }

    // ===== 4. LOGGING DES ÉCHECS DE CONNEXION =====
    
    /**
     * Logger les échecs de connexion (détection tentatives d'intrusion)
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        // 4a. Récupération de la requête HTTP pour contexte
        $request = $event->getRequest();
        
        // 4b. Récupération de l'exception d'authentification (raison de l'échec)
        $exception = $event->getException();

        // 4c. Extraction de l'email saisi (utilise getPayload() comme AppCustomAuthenticator)
        // Utilise getPayload() pour cohérence avec AppCustomAuthenticator
        $email = $request->getPayload()->getString('email', 'unknown');

        // 4d. Log niveau WARNING avec contexte complet (sécurité)
        $this->logger->warning('Échec de connexion', [
            'email' => $email,
            'ip' => $request->getClientIp(),
            'reason' => $exception->getMessage(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }
}