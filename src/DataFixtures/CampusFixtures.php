<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CampusFixtures extends Fixture
{
    public const CAMPUS = 'campus';
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $campus = new Campus();
            $campus->setName($faker->randomElement(['NANTES','RENNES','QUIMPER', 'NIORT']));

            $manager->persist($campus);
            $this->addReference(self::CAMPUS, $campus);

        }
        $manager->flush();
    }
}
