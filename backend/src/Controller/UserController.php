<?php

/**
 * USERCONTROLLER.PHP - Gestion du compte utilisateur
 * 
 * Responsabilités :
 * - Suppression du compte utilisateur avec vérification de mot de passe
 * - Nettoyage des données PostgreSQL (User, Projects, Tasks, Competences via CASCADE)
 * - Nettoyage des données MongoDB (Articles, Snippets via suppression manuelle)
 * - Déconnexion automatique après suppression
 */

namespace App\Controller;

use App\Document\Article;
use App\Document\Snippet;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/user', name: 'api_user_')]
class UserController extends AbstractController
{
    // ===== 1. SUPPRESSION DU COMPTE UTILISATEUR =====
    
    /**
     * DELETE /api/user/delete
     * Supprime le compte utilisateur et toutes ses donnees (PostgreSQL + MongoDB)
     */
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteAccount(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        DocumentManager $dm,
        Security $security
    ): JsonResponse
    {
        // 1a. Recuperer l'utilisateur connecte via le contexte de securite Symfony
        $user = $this->getUser();
        
        // 1b. Verification de l'authentification (securite défense en profondeur)
        if (!$user) {
            return $this->json(['error' => 'Non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Recuperer les donnees JSON de la requete (attendu : {"password": "..."})
        $data = json_decode($request->getContent(), true);

        // 3. Validation : mot de passe obligatoire pour confirmation de suppression
        if (empty($data['password'])) {
            return $this->json(['error' => 'Le mot de passe est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // 4. Verification du mot de passe avec hash bcrypt
        // Protection contre la suppression accidentelle ou malveillante
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Mot de passe incorrect'], Response::HTTP_FORBIDDEN);
        }

        // 5. Suppression des donnees MongoDB (MANUEL car pas de CASCADE)
        // MongoDB ne gere pas les relations CASCADE comme PostgreSQL
        try {
            // 5a. Suppression de tous les snippets de cet utilisateur
            // Recherche par userId stocke en string dans MongoDB
            $dm->createQueryBuilder(Snippet::class)
                ->remove()
                ->field('userId')->equals((string) $user->getId())
                ->getQuery()
                ->execute();

            // 5b. Suppression de tous les articles de cet utilisateur
            // Meme logique que pour les snippets
            $dm->createQueryBuilder(Article::class)
                ->remove()
                ->field('userId')->equals((string) $user->getId())
                ->getQuery()
                ->execute();

            // 5c. Persistance des suppressions MongoDB
            $dm->flush();
        } catch (\Exception $e) {
            // Gestion d'erreur : echec de suppression MongoDB (connexion, timeout, etc.)
            return $this->json([
                'error' => 'Erreur lors de la suppression des donnees MongoDB',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 6. Suppression de l'utilisateur PostgreSQL (CASCADE auto sur projects/tasks/competences)
        // Les entites liees sont automatiquement supprimees grace aux annotations Doctrine
        try {
            $em->remove($user);
            $em->flush();
        } catch (\Exception $e) {
            // Gestion d'erreur : echec de suppression PostgreSQL (contraintes, connexion, etc.)
            return $this->json([
                'error' => 'Erreur lors de la suppression du compte',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 7. Deconnexion de l'utilisateur apres suppression
        // Parametre false = ne pas declencher d'event de logout
        $security->logout(false);

        // 8. Reponse JSON de succes
        return $this->json([
            'success' => true,
            'message' => 'Compte supprime avec succes'
        ], Response::HTTP_OK);
    }
}