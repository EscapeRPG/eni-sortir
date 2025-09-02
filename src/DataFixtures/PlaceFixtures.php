<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Place;
use App\Entity\State;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PlaceFixtures extends Fixture
{
    public const PLACE = 'place';
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $place = new Place();
            $place->setName($faker->city())
                ->setStreet($faker->streetName())
                ->setPostalCode($faker->postcode())
                ->setCity($faker->city())
                ;

            $manager->persist($place);
            $this->addReference(self::PLACE. '_' .$i, $place);

        }
        $manager->flush();
    }
}
