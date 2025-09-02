<?php

namespace App\Form;

use App\Controller\PlaceController;
use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\Group;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
        $builder

//            ->add('groupePrive', CheckboxType::class, [
//                'label' => 'Groupe privé',
//                'required' => false,
//                'mapped' => false,
//                'attr' => ['id' => 'groupePriveCheckbox']
//            ])


            ->add('group', EntityType::class, [
                'class' => Group::class,
                'label' => 'Définir un groupe privé (optionnel)',
                'required' => false,
                'choice_label' => 'name',
                'placeholder' => '-- Garder cet évènement public --',
                'attr' => ['id' => 'groupSelect'],
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('g')->orderBy('g.name', 'ASC');
                }
            ])

            ->add('name', TextType::class, [
                'label' => 'Nom de l\'evènement',
                'required' => true,
            ])
            ->add('startingDateHour', DateTimeType::class, [
                'label'=>'Date de début',
                'widget' => 'single_text',
                'required' => true,
                /*'attr' => [
                    'placeholder' => date('d/m/y H:i'),
                ]*/
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
            /*la personne qui crée l'évènement est l'oganisateur, à ajouter*/

//            ->add('state', EntityType::class, [
//                'placeholder'=>'--Choisir un "état--',
//                'class' => State::class,
//                'choice_label' => function (State $state) {
//                    return sprintf('%s (%s)', $state->getLabel(), count($state->getEvents()));
//                },
//            ])
//
//            ->add('campus', EntityType::class, [
//                'placeholder'=>'--Choisir un campus--',
//                'class' => Campus::class,
//                'choice_label' => function (Campus $campus) {
//                    return sprintf('%s (%s)', $campus->getName(), count($campus->getEvents()));
//                }
//            ])
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
                    'class' => 'deactivate'
                ]
            ])
            ->add('publish', SubmitType::class, [
                'label' => 'Ouvrir aux inscriptions',
                'attr' => [
                    'class' => 'activate'
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
