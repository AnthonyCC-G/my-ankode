<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Competence;
use App\Document\Snippet;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter centralisé pour la gestion de l'ownership des ressources
 * 
 * Gère les permissions VIEW, EDIT, DELETE pour :
 * - Project (ownership via getOwner())
 * - Task (ownership via getProject()->getOwner())
 * - Competence (ownership via getOwner())
 * - Snippet MongoDB (ownership via getUserId())
 * 
 * Référentiel DWWM CP6 : Développer des composants d'accès aux données sécurisés
 */
class ResourceVoter extends Voter
{
    // Permissions supportées
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    /**
     * Détermine si ce Voter peut gérer cette permission + cette ressource
     * 
     * @param string $attribute La permission demandée (VIEW, EDIT, DELETE)
     * @param mixed $subject La ressource à vérifier (Project, Task, Competence, Snippet)
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // 1. Vérifie que l'attribute est bien VIEW, EDIT ou DELETE
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // 2. Vérifie que le subject est une entité/document qu'on gère
        return $subject instanceof Project
            || $subject instanceof Task
            || $subject instanceof Competence
            || $subject instanceof Snippet;
    }

    /**
     * Effectue la vérification d'ownership
     * 
     * @param string $attribute VIEW, EDIT ou DELETE
     * @param mixed $subject L'entité/document à vérifier
     * @param TokenInterface $token Le token de sécurité contenant l'utilisateur
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Récupérer l'utilisateur connecté
        $user = $token->getUser();

        // Si pas d'utilisateur connecté → REFUS automatique
        if (!$user instanceof User) {
            return false;
        }

        // Vérifier l'ownership selon le type de ressource
        return match (true) {
            // Project : ownership direct via getOwner()
            $subject instanceof Project => $subject->getOwner() === $user,
            
            // Task : ownership indirect via getProject()->getOwner()
            $subject instanceof Task => $subject->getProject()->getOwner() === $user,
            
            // Competence : ownership direct via getOwner()
            $subject instanceof Competence => $subject->getOwner() === $user,
            
            // Snippet MongoDB : ownership via getUserId() (string comparison)
            $subject instanceof Snippet => $subject->getUserId() === (string) $user->getId(),
            
            // Cas par défaut (ne devrait jamais arriver si supports() est bien codé)
            default => false,
        };
    }
}