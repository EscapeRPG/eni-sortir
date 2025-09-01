<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $event = new Event();
            $event->setName($faker->realText(30))
                ->setStartingDateHour($faker->dateTimeBetween('+1 day', '+3 month'))
                ->setEndDateHour($faker->dateTimeBetween($event->getStartingDateHour(), '+3 day'))
                ->setNbInscriptionsMax($faker->numberBetween(3,100))
                ->setEventInfo($faker->paragraph(2));

        }
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
