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
        // 1- ANTHONY - ADMIN (Formateur/Mentor)
        // Compte le plus ancien (6 mois) = fondateur de la plateforme
        $anthony = new User();
        $anthony->setEmail('anthony@test.com');
        $anthony->setUsername('anthony_dev');
        $anthony->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($anthony, 'password123');
        $anthony->setPassword($hashedPassword);
        $anthony->setCreatedAt(new \DateTimeImmutable('-6 months'));
        
        $manager->persist($anthony);
        $this->addReference('user_anthony', $anthony);

        // 2- ALICE - Dev Junior Frontend (Fan de React/Angular)
        // Inscrite il y a 3 mois
        $alice = new User();
        $alice->setEmail('alice@test.com');
        $alice->setUsername('alice_codes');
        $alice->setRoles(['ROLE_USER']);
        
        $hashedPasswordAlice = $this->passwordHasher->hashPassword($alice, 'password123');
        $alice->setPassword($hashedPasswordAlice);
        $alice->setCreatedAt(new \DateTimeImmutable('-3 months'));
        
        $manager->persist($alice);
        $this->addReference('user_alice', $alice);

        // 3- BOB - Dev Junior Backend (Adore Symfony)
        // Inscrit il y a 2 mois
        $bob = new User();
        $bob->setEmail('bob@test.com');
        $bob->setUsername('bob_debug');
        $bob->setRoles(['ROLE_USER']);
        
        $hashedPasswordBob = $this->passwordHasher->hashPassword($bob, 'password123');
        $bob->setPassword($hashedPasswordBob);
        $bob->setCreatedAt(new \DateTimeImmutable('-2 months'));
        
        $manager->persist($bob);
        $this->addReference('user_bob', $bob);

        // 4- CLARA - Dev en Reconversion (Ex-prof de maths)
        // Nouvelle venue (1 mois)
        $clara = new User();
        $clara->setEmail('clara@test.com');
        $clara->setUsername('clara_learns');
        $clara->setRoles(['ROLE_USER']);
        
        $hashedPasswordClara = $this->passwordHasher->hashPassword($clara, 'password123');
        $clara->setPassword($hashedPasswordClara);
        $clara->setCreatedAt(new \DateTimeImmutable('-1 month'));
        
        $manager->persist($clara);
        $this->addReference('user_clara', $clara);

        $manager->flush();
    }
}