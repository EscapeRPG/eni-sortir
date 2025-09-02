<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\State;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class StateFixtures extends Fixture
{
    public const STATE = 'state';
    public function load(ObjectManager $manager): void
    {
        $states = ['Créée', 'Ouverte', 'Cloturée', 'En cours', 'Passée', 'Annulée', 'Archivée'];

        foreach ($states as $i => $label) {
            $state = new State();
            $state->setLabel($label);

            $manager->persist($state);
            $this->addReference(self::STATE . '_' . $i, $state);
        }

        $manager->flush();
    }
}
