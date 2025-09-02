<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CampusFixtures extends Fixture
{
    public const CAMPUS = 'campus';
    public function load(ObjectManager $manager): void
    {
        $campusNames = ['NANTES', 'RENNES', 'QUIMPER', 'NIORT'];

        foreach ($campusNames as $i => $name) {
            $campus = new Campus();
            $campus->setName($name);

            $manager->persist($campus);
            $this->addReference(self::CAMPUS . '_' . $i, $campus);
        }

        $manager->flush();
    }
}
