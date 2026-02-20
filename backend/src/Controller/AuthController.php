<?php

/**
 * AUTHCONTROLLER.PHP - Gestion de l'authentification (inscription + connexion)
 * 
 * Responsabilités :
 * - Afficher la page d'authentification combinée (login + register dans une seule vue)
 * - Traiter l'inscription (POST) avec validation du formulaire Symfony
 * - Hachage sécurisé du mot de passe (bcrypt via UserPasswordHasher)
 * - Redirection automatique vers dashboard si déjà connecté
 * - Gestion des erreurs de connexion via AuthenticationUtils
 * 
 * Architecture :
 * - Page publique (pas de #[IsGranted])
 * - Formulaire d'inscription POST vers /auth/register
 * - Formulaire de connexion géré par Symfony Security (app_login)
 * - Messages flash pour feedback utilisateur
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    // ===== 1. AFFICHAGE DE LA PAGE D'AUTHENTIFICATION (GET + POST) =====
    
    /**
     * Affiche la page d'authentification (inscription + connexion)
     */
    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // 1a. Redirection si l'utilisateur est déjà authentifié
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // ===== PARTIE CONNEXION (Login) =====
        $loginError = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // ===== PARTIE INSCRIPTION (Register) =====
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_auth_register'), //  ACTION VERS /auth/register
        ]);

        return $this->render('auth/index.html.twig', [
            'registrationForm' => $registrationForm,
            'last_username' => $lastUsername,
            'login_error' => $loginError,
        ]);
    }

    // ===== 2. TRAITEMENT DE L'INSCRIPTION (POST UNIQUEMENT) =====
    
    /**
     * Traite l'inscription (POST uniquement)
     */
    #[Route('/auth/register', name: 'app_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        AuthenticationUtils $authenticationUtils
    ): Response {
        // 2a. Création d'une nouvelle entité User
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);
        $registrationForm->handleRequest($request);

        // 2b. Validation et traitement du formulaire
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $registrationForm->get('plainPassword')->getData();

            // Hash du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Enregistrer la date d'acceptation des CGU et de la collecte de donnees
            $user->setTermsAcceptedAt(new \DateTimeImmutable());
            $user->setDataCollectionAcceptedAt(new \DateTimeImmutable());

            // Sauvegarde en BDD
            $entityManager->persist($user);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_auth');
        }

        // Si le formulaire a des erreurs, réaffiche la page avec les erreurs
        $loginError = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/index.html.twig', [
            'registrationForm' => $registrationForm,
            'last_username' => $lastUsername,
            'login_error' => $loginError,
        ]);
    }
}