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
        // 1️⃣ ADMIN 
        $admin = new User();
        $admin->setEmail('anthony@test.com');
        $admin->setUsername('anthony_dev');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']); // Les deux rôles explicites
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'password123'
        );
        $admin->setPassword($hashedPassword);
        $admin->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($admin);
        
        // Référence pour tests admin futurs
        $this->addReference('user_anthony', $admin);

        // 2️⃣ ALICE - Utilisatrice lambda #1 (reprend les données d'Anthony)
        $alice = new User();
        $alice->setEmail('alice@test.com');
        $alice->setUsername('alice_user');
        $alice->setRoles(['ROLE_USER']); // Simple utilisatrice
        
        $hashedPasswordAlice = $this->passwordHasher->hashPassword(
            $alice,
            'password123'
        );
        $alice->setPassword($hashedPasswordAlice);
        $alice->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($alice);
        
        // Référence pour ProjectFixtures, TaskFixtures, CompetenceFixtures
        $this->addReference('user_alice', $alice);

        // 3️⃣ MARIE - Utilisatrice lambda #2 (garde ses données)
        $marie = new User();
        $marie->setEmail('marie@test.com');
        $marie->setUsername('marie_user');
        $marie->setRoles(['ROLE_USER']); // Simple utilisatrice
        
        $hashedPasswordMarie = $this->passwordHasher->hashPassword(
            $marie,
            'password123'
        );
        $marie->setPassword($hashedPasswordMarie);
        $marie->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($marie);
        
        // Référence pour ProjectFixtures, TaskFixtures, CompetenceFixtures
        $this->addReference('user_marie', $marie);

        $manager->flush();
    }
}