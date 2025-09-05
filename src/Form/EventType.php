<?php

namespace App\Form;


use App\Entity\Event;
use App\Entity\Group;
use App\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;


class EventType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $userId = $user->getId();
        $groupRepository = $options['group_repository'];

        $builder->add('group', EntityType::class, [
            'class' => Group::class,
            'label' => 'Définir un groupe privé (optionnel)',
            'required' => false,
            'choice_label' => 'name',
            'placeholder' => '-- Garder cet événement public --',
            'attr' => ['id' => 'groupSelect'],
            'query_builder' => fn() => $groupRepository->findGroupsOfUserConnected($userId),
        ])

            ->add('name', TextType::class, [
                'label' => 'Nom de l\'événement',
                'required' => true,
            ])
            ->add('startingDateHour', DateTimeType::class, [
                'label'=>'Date de début',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('endDateHour', DateTimeType::class, [
                'label'=>'Date de fin',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'label'=>'Clôture des inscriptions',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label'=>'Nombre maximum de participants',
                'required' => true,
            ])
            ->add('eventInfo', TextareaType::class, [
                'label' => 'Infos pratiques',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                ]
            ])

            ->add('place', EntityType::class, [
                'class' => Place::class,
                'label'=>'Choisissez un lieu',
                'required' => true,
                'choice_label' => 'name',
            ])
            ->add('poster_file', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'Fichier trop lourd',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Les formats de fichiers acceptés sont JPEG, JPG et PNG',
                    ])
                ]
            ])
            ->add('saveDraft', SubmitType::class, [
                'label' => 'Enregistrer pour plus tard',
                'attr' => [
                    'class' => 'deactivate link-btn'
                ]
            ])
            ->add('publish', SubmitType::class, [
                'label' => 'Ouvrir aux inscriptions',
                'attr' => [
                    'class' => 'activate link-btn'
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'user' => null,
            'group_repository' => null,
        ]);

        $resolver->setAllowedTypes('group_repository', ['null', 'App\Repository\GroupRepository']);
        $resolver->setAllowedTypes('user', ['null', 'App\Entity\User']);
    }

}
