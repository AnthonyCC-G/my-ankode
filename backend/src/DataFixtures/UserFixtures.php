<?php

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
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('anthony@test.com');
        $user->setUsername('anthony_dev');
        $user->setRoles(['ROLE_USER']);
        
        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password123'  // Mot de passe en clair
        );
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new \DateTimeImmutable());  // ← CORRIGÉ

        $manager->persist($user);
        $manager->flush();

        // Créer une référence pour les autres fixtures
        $this->addReference('user_anthony', $user);
    }
}