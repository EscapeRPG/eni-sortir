<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class EditType extends AbstractType
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
                'label' => 'Nom ',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom '
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email ',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[^@]+@campus-eni\.fr$/',
                        'message' => "L'adresse email doit appartenir au domaine @campus-eni.fr"
                    ])
                ]
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone ',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^((0[1-9])|(\+33))[ .-]?((?:[ .-]?\d{2}){4}|\d{8})$/',
                        'message' => "Le numéro de téléphone doit commencer par 0 ou +33."
                    ]),
                ]
            ]);
      
        if ($options['is_self_edit']) {
            $builder
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'options' => [
                        'attr' => [
                            'autocomplete' => 'new-password',
                        ],
                    ],
                    'first_options' => [
                        'constraints' => [
                            new Length([
                                // max length allowed by Symfony for security reasons
                                'max' => 4096,
                            ]),
                            new Regex([
                                'pattern' => '/^(?=.*[A-Z])(?=.*[@#$%^&*+=!?]).{8,}$/',
                                'message' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial',
                            ]),
                        ],
                        'label' => 'Nouveau mot de passe ',
                        'required' => false,
                    ],
                    'second_options' => [
                        'label' => 'Répéter votre mot de passe ',
                    ],
                    'invalid_message' => 'Le mot de passe est différent.',
                    // Instead of being set onto the object directly,
                    // this is read and encoded in the controller
                    'mapped' => false,
                    'required' => false,
                ]);
        }
        if ($options['is_admin']) {
            $builder
                ->add('campus', EntityType::class, [
                    'label' => 'Campus ',
                    'class' => Campus::class,
                    'choice_label' => 'name',
                ]);
        }
        $builder
            ->add('submit', SubmitType::class, [
            'label' => 'Enregistrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
            'is_self_edit' => false,
        ]);
    }
}
