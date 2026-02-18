<?php

/**
 * REGISTRATIONFORMTYPE.PHP - Formulaire Symfony pour l'inscription utilisateur
 * 
 * Responsabilités :
 * - Définir les champs du formulaire d'inscription (email, username, password)
 * - Valider les contraintes côté serveur (password min 6 car, CGU acceptées)
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
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            
            // 1d. Champ mot de passe (non mappé, sera hashé dans le controller)
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
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