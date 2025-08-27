<?php

namespace App\Form;

use App\Entity\Place;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom'])
            ->add('street', TextType::class, [
                'label' => 'rue'])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal'])
            ->add('city', TextType::class, [
                'label' => 'Ville'])
            ->add('latitude', IntegerType::class, [
                'label' => 'Latitude'
            ])
            ->add('longitude', IntegerType::class, [
                'label' => 'Longitude'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer'
            ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
