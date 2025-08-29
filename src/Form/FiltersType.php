<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'label' => 'Campus ',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Le nom de l\'événement contient ',
                'required' => false,
            ])
            ->add('startingDay', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début ',
                'required' => false,
            ])
            ->add('endingDay', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin ',
                'required' => false,
            ])
            ->add('organizer', CheckboxType::class, [
                'label' => '- dont je suis l\'organisateur ',
                'required' => false,
            ])
            ->add('subscribed', CheckboxType::class, [
                'label' => '- auxquels je participe ',
                'required' => false,
            ])
            ->add('notSubscribed', CheckboxType::class, [
                'label' => '- auxquels je ne participe pas ',
                'required' => false,
            ])
            ->add('passedEvents', CheckboxType::class, [
                'label' => '- passés ',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer ',
            ])
        ;
    }
}
