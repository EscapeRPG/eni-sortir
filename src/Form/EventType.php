<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
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

            ->add('nbInscriptionsMax', IntegerType::class, [
                'label'=>'Nombre d\'inscriptions',
                'required' => true,
            ])

            ->add('eventInfo', TextareaType::class, [
                'label' => 'Information',
                'required' => false,
            ])

            /*la personne qui crée l'évènement est l'oganisateur, à ajouter*/

            ->add('state', EntityType::class, [
                'placeholder'=>'--Choisir un "état--',
                'class' => State::class,
                'choice_label' => function (State $state) {
                    return sprintf('%s (%s)', $state->getLabel(), count($state->getEvents()));
                },


            ])
            ->add('campus', EntityType::class, [
                'placeholder'=>'--Choisir un campus--',
                'class' => Campus::class,
                'choice_label' => function (Campus $campus) {
                    return sprintf('%s (%s)', $campus->getName(), count($campus->getEvents()));
                }
            ])

            ->add('place', TextType::class, [
                'label'=>'Lieux',
                'required' => true,
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
