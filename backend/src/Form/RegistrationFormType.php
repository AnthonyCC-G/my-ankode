<?php

/**
 * REGISTRATIONFORMTYPE.PHP - Formulaire Symfony pour l'inscription utilisateur
 * 
 * Responsabilités :
 * - Définir les champs du formulaire d'inscription (email, username, password)
 * - Valider les contraintes côté serveur (password min 16 car + complexite ANSSI)
 * - Gérer le champ plainPassword (non mappé, lu dans le controller pour hachage)
 * - Mapper les données au modèle User (Entity)
 * 
 * Architecture :
 * - Form Type Symfony (AbstractType)
 * - Champs mappés : email, username (écrits dans User)
 * - Champs non mappés : agreeTerms, plainPassword (traités dans le controller)
 * - Contraintes de validation : NotBlank, Length, IsTrue
 * 
 * Sécurité :
 * - plainPassword non mappé : évite stockage accidentel en clair
 * - Length min 6 caractères (sécurité faible, mais défini)
 * - Length max 4096 (limite Symfony pour raisons de sécurité)
 * - CGU obligatoires via IsTrue constraint
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    // ===== 1. CONSTRUCTION DU FORMULAIRE - CHAMPS ET VALIDATIONS =====
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1a. Champ email (mappé automatiquement à User::$email)
            ->add('email')
            
            // 1b. Champ username (mappé automatiquement à User::$username)
            ->add('username')
            
            // 1c. Checkbox CGU (non mappé, validation uniquement)
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions.',
                    ]),
                ],
            ])
            
            // 1d. Champ mot de passe (non mappé, sera hashé dans le controller)
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 16,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractere special',
                    ]),
                ],
            ])

            // 1e. Checkbox collecte de données personnelles (RGPD)
            ->add('agreeDataCollection', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter la collecte de vos donnees personnelles.',
                    ]),
                ],
            ])
        ;
    }

    // ===== 2. CONFIGURATION DU FORMULAIRE - LIAISON AU MODÈLE USER =====
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Liaison du formulaire à l'entité User (data_class)
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}