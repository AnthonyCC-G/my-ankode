<?php

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
        // 1. Recuperer l'utilisateur connecte
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Recuperer les donnees JSON de la requete
        $data = json_decode($request->getContent(), true);

        // 3. Validation : mot de passe obligatoire
        if (empty($data['password'])) {
            return $this->json(['error' => 'Le mot de passe est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // 4. Verification du mot de passe
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Mot de passe incorrect'], Response::HTTP_FORBIDDEN);
        }

        // 5. Suppression des donnees MongoDB (MANUEL car pas de CASCADE)
        try {
            // Suppression de tous les snippets de cet utilisateur
            $dm->createQueryBuilder(Snippet::class)
                ->remove()
                ->field('userId')->equals((string) $user->getId())
                ->getQuery()
                ->execute();

            // Suppression de tous les articles de cet utilisateur
            $dm->createQueryBuilder(Article::class)
                ->remove()
                ->field('userId')->equals((string) $user->getId())
                ->getQuery()
                ->execute();

            $dm->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression des donnees MongoDB',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 6. Suppression de l'utilisateur PostgreSQL (CASCADE auto sur projects/tasks/competences)
        try {
            $em->remove($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression du compte',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 7. Deconnexion de l'utilisateur
        $security->logout(false);

        // 8. Reponse JSON de succes
        return $this->json([
            'success' => true,
            'message' => 'Compte supprime avec succes'
        ], Response::HTTP_OK);
    }
}