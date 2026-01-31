<?php

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
    private const API_PREFIX = '/api/';
    private const METHODS_REQUIRING_CSRF = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const EXCLUDED_ROUTES = [
        '/api/csrf-token',
    ];

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // Vérifier si c'est une route API
        if (!str_starts_with($path, self::API_PREFIX)) {
            return;
        }

        // Vérifier si la route est exclue
        if (in_array($path, self::EXCLUDED_ROUTES)) {
            return;
        }

        // Vérifier si la méthode nécessite une validation CSRF
        if (!in_array($method, self::METHODS_REQUIRING_CSRF)) {
            return;
        }

        // Valider le token CSRF
        $token = $request->headers->get('X-CSRF-Token');

        if (!$token) {
            throw new BadRequestHttpException('Token CSRF manquant');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('api', $token))) {
            throw new BadRequestHttpException('Token CSRF invalide');
        }
    }
}
