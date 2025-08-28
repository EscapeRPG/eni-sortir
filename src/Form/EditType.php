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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
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
        ]);
    }
}
