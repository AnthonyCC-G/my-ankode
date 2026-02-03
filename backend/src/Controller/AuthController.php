<?php

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
    /**
     * Affiche la page d'authentification (inscription + connexion)
     */
    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, redirection vers dashboard
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
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);
        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $registrationForm->get('plainPassword')->getData();

            // Hash du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

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