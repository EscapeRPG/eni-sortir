<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\User;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilPicture', FileType::class, [
                'required' => false,
                'label' => 'Photo de profil ',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'Votre fichier est trop lourd !',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Les formats acceptés sont jpg, png ou jpeg !',
                    ])
                ]
            ])
            ->add('name', TextType::class, [
                'label'=> 'Nom* '
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom* '
            ])
            ->add('phoneNumber' , TextType::class, [
                'label' => 'Numéro de téléphone* ',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^((0[1-9])|(\+33))[ .-]?((?:[ .-]?\d{2}){4}|\d{8})$/',
                        'message' => "Le numéro de téléphone doit commencer par 0 ou +33."
                    ]),
                ]
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'label' => 'Campus* ',
                'placeholder' => '-- Choisir un campus --',
                'choice_label' => 'name'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email* ',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[^@]+@campus-eni\.fr$/',
                        'message' => "L'adresse email doit appartenir au domaine @campus-eni.fr"
                    ])
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                                'mapped' => false,
                'label' => 'J\'accepte les termes d\'utilisation ',
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label' => 'Mot de passe* ',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*[@#$%^&*+=!?]).{8,}$/',
                        'message' => 'Le mot de passe doit contenir au moins 8 carcactères, une majuscule et un caractère spécial',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
