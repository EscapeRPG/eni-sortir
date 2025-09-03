<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'label' => 'Campus ',
                'placeholder' => '--- Choisissez un campus ---',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer ',
                'attr' => [
                    'class' => 'link-btn',
                ]
            ])
        ;
    }
}
