<?php

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
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    /**
     * Logger les connexions réussies avec IP et User-Agent
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();

        $this->logger->info('Connexion réussie', [
            'user' => $user->getUserIdentifier(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }

    /**
     * Logger les échecs de connexion (détection tentatives d'intrusion)
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        // Utilise getPayload() pour cohérence avec AppCustomAuthenticator
        $email = $request->getPayload()->getString('email', 'unknown');

        $this->logger->warning('Échec de connexion', [
            'email' => $email,
            'ip' => $request->getClientIp(),
            'reason' => $exception->getMessage(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }
}