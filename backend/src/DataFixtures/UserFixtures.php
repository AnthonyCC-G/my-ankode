<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1️⃣ ADMIN (toi, pour les tests complets)
        $admin = new User();
        $admin->setEmail('anthony@test.com');
        $admin->setUsername('anthony_dev');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']); // Rôle admin
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'password123'
        );
        $admin->setPassword($hashedPassword);
        $admin->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($admin);
        
        // Créer une référence pour les autres fixtures
        $this->addReference('user_anthony', $admin);

        // 2️⃣ UTILISATEUR LAMBDA (pour tester les permissions)
        $userLambda = new User();
        $userLambda->setEmail('marie@test.com');
        $userLambda->setUsername('marie_user');
        $userLambda->setRoles(['ROLE_USER']); // Simple utilisateur
        
        $hashedPasswordLambda = $this->passwordHasher->hashPassword(
            $userLambda,
            'password123'
        );
        $userLambda->setPassword($hashedPasswordLambda);
        $userLambda->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($userLambda);
        
        // Créer une référence pour ProjectFixtures
        $this->addReference('user_marie', $userLambda);

        $manager->flush();
    }
}