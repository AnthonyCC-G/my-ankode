<?php

/**
 * SECURITYCONTROLLER.PHP - Gestion de l'authentification et des tokens CSRF
 * 
 * Responsabilités :
 * - Fournir les tokens CSRF pour les appels API JavaScript
 * - Gérer l'affichage du formulaire de login
 * - Gérer la déconnexion (délégué au firewall Symfony)
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SecurityController extends AbstractController
{
    // ===== 1. ENDPOINT API CSRF TOKEN =====
    
    /**
     * Route API pour récupérer un token CSRF valide
     * Utilisée par le frontend JavaScript avant les requêtes POST/PUT/DELETE
     */
    #[Route('/api/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCsrfToken(CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        // Génération d'un token CSRF avec l'identifiant 'api'
        // Ce token doit être inclus dans le header X-CSRF-Token des requêtes modifiantes
        return $this->json([
            'token' => $csrfTokenManager->getToken('api')->getValue()
        ]);
    }
    
    // ===== 2. AFFICHAGE DU FORMULAIRE DE LOGIN =====
    
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        // Récupération de l'erreur de login s'il y en a une (credentials invalides)
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Récupération du dernier email saisi par l'utilisateur (pré-remplissage du formulaire)
        $lastUsername = $authenticationUtils->getLastUsername();

        // Rendu du template Twig avec les données de pré-remplissage et l'erreur éventuelle
        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    // ===== 3. DÉCONNEXION (GÉRÉE PAR LE FIREWALL) =====
    
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode ne sera jamais exécutée car interceptée par le firewall Symfony
        // Configuration dans config/packages/security.yaml sous la clé 'logout'
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}