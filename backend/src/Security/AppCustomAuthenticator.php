<?php

/**
 * APPCUSTOMAUTHENTICATOR.PHP - Authentificateur personnalisé Symfony
 * 
 * Responsabilités :
 * - Gérer l'authentification par formulaire (email + password)
 * - Valider le token CSRF du formulaire de login
 * - Gérer la fonctionnalité "Remember Me" (session persistante)
 * - Rediriger vers dashboard après connexion réussie
 * - Rediriger vers page de login en cas d'échec
 * 
 * Architecture :
 * - Authenticator Symfony (AbstractLoginFormAuthenticator)
 * - Utilise Passport API (Symfony 5.4+)
 * - Badges : UserBadge, PasswordCredentials, CsrfTokenBadge, RememberMeBadge
 * - TargetPathTrait : redirection vers page demandée avant login
 * 
 * Sécurité :
 * - Protection CSRF via CsrfTokenBadge (token 'authenticate')
 * - Vérification automatique du password via PasswordCredentials
 * - Email stocké en session pour pré-remplissage en cas d'échec
 * - Rate Limiting : 3 tentatives max / 15 minutes par IP (anti brute force)
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    // ===== 1. TRAIT SYMFONY - GESTION DU TARGET PATH =====

    use TargetPathTrait;

    // ===== 2. CONSTANTE - ROUTE DE LOGIN =====

    public const LOGIN_ROUTE = 'app_auth';

    // ===== 3. INJECTION DE DÉPENDANCES =====

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        // RateLimiterFactory injectée par autowiring via le nom $loginLimiterFactory
        // qui correspond à la clé 'login_limiter' déclarée dans rate_limiter.yaml
        private RateLimiterFactory $loginLimiterFactory
    ) {}

    // ===== 4. AUTHENTIFICATION - VALIDATION DES CREDENTIALS =====

    public function authenticate(Request $request): Passport
    {
        // ===== 4a. RATE LIMITING - BLOQUER EN AMONT (avant toute requête BDD) =====
        // On crée un limiteur unique par adresse IP
        $limiter = $this->loginLimiterFactory->create($request->getClientIp());

        // On consomme 1 jeton sur les 3 disponibles
        // Si la limite est dépassée, isAccepted() retourne false
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Trop de tentatives de connexion. Réessayez dans 15 minutes.'
            );
        }

        // 4b. Extraction de l'email depuis le payload de la requête POST
        $email = $request->getPayload()->getString('email');

        // 4c. Stockage de l'email en session pour pré-remplissage en cas d'échec
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // 4d. Construction du Passport Symfony avec badges de sécurité
        return new Passport(
            new UserBadge($email), // Identifiant de l'utilisateur (email)
            new PasswordCredentials($request->getPayload()->getString('password')), // Mot de passe à vérifier
            [
                // Badge CSRF : validation du token avec identifiant 'authenticate'
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                // Badge Remember Me : gestion de la session persistante
                new RememberMeBadge(),
            ]
        );
    }

    // ===== 5. REDIRECTION APRÈS CONNEXION RÉUSSIE =====

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // 5a. Vérification si une page était demandée avant redirection vers login
        // TargetPathTrait permet de récupérer cette URL
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // 5b. Redirection par défaut vers le dashboard
        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    // ===== 6. URL DE LA PAGE DE LOGIN =====

    protected function getLoginUrl(Request $request): string
    {
        // Génération de l'URL de la route de login (app_auth)
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}