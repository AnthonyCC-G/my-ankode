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

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    // ===== 1. TRAIT SYMFONY - GESTION DU TARGET PATH =====
    
    use TargetPathTrait;

    // ===== 2. CONSTANTE - ROUTE DE LOGIN =====
    
    public const LOGIN_ROUTE = 'app_auth';

    // ===== 3. INJECTION DE DÉPENDANCE - URL GENERATOR =====
    
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    // ===== 4. AUTHENTIFICATION - VALIDATION DES CREDENTIALS =====
    
    public function authenticate(Request $request): Passport
    {
        // 4a. Extraction de l'email depuis le payload de la requête POST
        $email = $request->getPayload()->getString('email');

        // 4b. Stockage de l'email en session pour pré-remplissage en cas d'échec
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // 4c. Construction du Passport Symfony avec badges de sécurité
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

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        
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