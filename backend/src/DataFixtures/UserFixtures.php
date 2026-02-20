<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * UserFixtures - Jeu de données de test pour les utilisateurs
 * 
 * Charge en base de données un ensemble d'utilisateurs fictifs
 * permettant de tester les fonctionnalités de l'application.
 * 
 * Les mots de passe respectent la politique de sécurité :
 * - Minimum 16 caractères
 * - Au moins une majuscule, une minuscule, un chiffre et un caractère spécial
 * 
 * Commande de chargement : php bin/console doctrine:fixtures:load --group=user
 */
class UserFixtures extends Fixture implements FixtureGroupInterface
{
    // Service de hachage des mots de passe injecté via le constructeur
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * Injection du service de hachage des mots de passe
     * Le constructeur reçoit automatiquement le service via l'autowiring Symfony
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        // Stockage du service dans une propriété pour utilisation dans load()
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Méthode principale appelée par Symfony lors du chargement des fixtures
     * L'ObjectManager est l'interface Doctrine permettant de persister les entités
     */
    public function load(ObjectManager $manager): void
    {
        // ===== 1. ANTHONY - ADMIN (Formateur/Mentor) =====
        // Compte le plus ancien (6 mois) = fondateur de la plateforme

        // Instanciation d'un nouvel objet User (entité Doctrine)
        $anthony = new User();

        // Définition de l'adresse email (identifiant de connexion)
        $anthony->setEmail('anthony@test.com');

        // Définition du nom d'affichage public
        $anthony->setUsername('anthony_dev');

        // Attribution des rôles : ROLE_USER (accès standard) + ROLE_ADMIN (accès administration)
        $anthony->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        // Hachage du mot de passe en clair via le service UserPasswordHasher
        // Le mot de passe ne doit jamais être stocké en clair en base de données
        $hashedPassword = $this->passwordHasher->hashPassword($anthony, 'Anthony@Fixture2026');

        // Stockage du mot de passe haché dans l'entité
        $anthony->setPassword($hashedPassword);

        // Date de création du compte (simulation d'un compte existant depuis 6 mois)
        $anthony->setCreatedAt(new \DateTimeImmutable('-6 months'));

        // Date d'acceptation des conditions générales d'utilisation (CGU)
        $anthony->setTermsAcceptedAt(new \DateTimeImmutable('-6 months'));

        // Date d'acceptation de la politique de collecte des données (RGPD)
        $anthony->setDataCollectionAcceptedAt(new \DateTimeImmutable('-6 months'));

        // Mise en file d'attente de l'entité pour insertion en base (pas encore en BDD)
        $manager->persist($anthony);

        // Enregistrement d'une référence pour utilisation dans d'autres fixtures
        // Permet à ProjectFixtures, TaskFixtures etc. de récupérer cet utilisateur
        $this->addReference('user_anthony', $anthony);


        // ===== 2. ALICE - Dev Junior Frontend (Fan de React/Angular) =====
        // Inscrite il y a 3 mois

        $alice = new User();
        $alice->setEmail('alice@test.com');
        $alice->setUsername('alice_codes');
        $alice->setRoles(['ROLE_USER']);

        $hashedPasswordAlice = $this->passwordHasher->hashPassword($alice, 'Alice@Fixture2026!');
        $alice->setPassword($hashedPasswordAlice);

        $alice->setCreatedAt(new \DateTimeImmutable('-3 months'));
        $alice->setTermsAcceptedAt(new \DateTimeImmutable('-3 months'));
        $alice->setDataCollectionAcceptedAt(new \DateTimeImmutable('-3 months'));

        $manager->persist($alice);
        $this->addReference('user_alice', $alice);


        // ===== 3. BOB - Dev Junior Backend (Adore Symfony) =====
        // Inscrit il y a 2 mois

        $bob = new User();
        $bob->setEmail('bob@test.com');
        $bob->setUsername('bob_debug');
        $bob->setRoles(['ROLE_USER']);

        $hashedPasswordBob = $this->passwordHasher->hashPassword($bob, 'Bob@Fixture2026!!');
        $bob->setPassword($hashedPasswordBob);

        $bob->setCreatedAt(new \DateTimeImmutable('-2 months'));
        $bob->setTermsAcceptedAt(new \DateTimeImmutable('-2 months'));
        $bob->setDataCollectionAcceptedAt(new \DateTimeImmutable('-2 months'));

        $manager->persist($bob);
        $this->addReference('user_bob', $bob);


        // ===== 4. CLARA - Dev en Reconversion (Ex-prof de maths) =====
        // Nouvelle venue (1 mois)

        $clara = new User();
        $clara->setEmail('clara@test.com');
        $clara->setUsername('clara_learns');
        $clara->setRoles(['ROLE_USER']);

        $hashedPasswordClara = $this->passwordHasher->hashPassword($clara, 'Clara@Fixture2026!');
        $clara->setPassword($hashedPasswordClara);

        $clara->setCreatedAt(new \DateTimeImmutable('-1 month'));
        $clara->setTermsAcceptedAt(new \DateTimeImmutable('-1 month'));
        $clara->setDataCollectionAcceptedAt(new \DateTimeImmutable('-1 month'));

        $manager->persist($clara);
        $this->addReference('user_clara', $clara);


        // Envoi de toutes les entités en attente vers la base de données en une seule requête
        // flush() déclenche les INSERT SQL pour tous les persist() précédents
        $manager->flush();
    }

    /**
     * Définit le groupe auquel appartient cette fixture
     * Permet le chargement ciblé : --group=user
     * Sans cette méthode, la fixture serait chargée avec toutes les autres
     */
    public static function getGroups(): array
    {
        return ['user'];
    }
}