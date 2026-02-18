<?php

/**
 * REGISTRATIONCONTROLLER.PHP - Gestion de l'inscription utilisateur (route obsolète)
 * 
 * Responsabilités :
 * - Afficher le formulaire d'inscription (GET /register)
 * - Traiter l'inscription utilisateur (POST /register)
 * - Hachage sécurisé du mot de passe (bcrypt via UserPasswordHasher)
 * - Redirection vers /login après inscription réussie
 * 
 * Architecture :
 * - Route publique (pas de #[IsGranted])
 * - Formulaire Symfony RegistrationFormType
 * - Stockage dans PostgreSQL (Entity\User)
 * 
 * NOTE : Cette route /register est historique et peu utilisée.
 *        L'inscription principale se fait via /auth (AuthController).
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    // ===== 1. FORMULAIRE D'INSCRIPTION (GET + POST) =====
    
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // 1a. Création d'une nouvelle entité User vide pour le formulaire
        $user = new User();
        
        // 1b. Création et binding du formulaire Symfony
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // 1c. Validation et traitement du formulaire (soumis + valide)
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // 1d. Persistance de l'utilisateur en base de données PostgreSQL
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            // 1e. Redirection vers la page de login après inscription réussie
            return $this->redirectToRoute('app_login');
        }

        // 1f. Rendu du template Twig avec le formulaire (GET ou POST avec erreurs)
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}