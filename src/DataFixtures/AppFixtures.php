<?php

namespace App\DataFixtures;

use App\Entity\Campus;
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
                ->setDuration($event->getEndDateHour()-$event->getStartingDateHour())
                ->setNbInscriptionsMax($faker->numberBetween(3,100))
                ->setEventInfo($faker->paragraph(2))
                ->setCampus($this->getReference(CampusFixtures::CAMPUS, Campus::class))

            ;

        }

        $manager->persist($event);

        $manager->flush();
    }
}
