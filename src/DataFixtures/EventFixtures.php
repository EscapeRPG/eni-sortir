<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\Place;
use App\Entity\User;
use App\Entity\State;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CampusFixtures::class, UserFixtures::class, StateFixtures::class, PlaceFixtures::class];
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 20; $i++) {
            $event = new Event();
            $event->setName($faker->realText(30))
                ->setStartingDateHour($faker->dateTimeBetween('+1 day', '+3 month'))
                ->setEndDateHour($faker->dateTimeBetween($event->getStartingDateHour()->format('Y-m-d H:i:s') . ' +1 second', $event->getStartingDateHour()->format('Y-m-d H:i:s') . ' +3 days'))
                ->setDuration(($event->getEndDateHour()->getTimestamp() - $event->getStartingDateHour()->getTimestamp())/60)
                ->setNbInscriptionsMax($faker->numberBetween(3,100))
                ->setRegistrationDeadline($faker->dateTimeBetween('now', $event->getStartingDateHour()))
                ->setEventInfo($faker->paragraph(2))
                ->setState($this->getReference(StateFixtures::STATE . '_1', State::class))
                ->setPlace($this->getReference(PlaceFixtures::PLACE . '_' . $faker->numberBetween(0,9), Place::class))
                ->setCampus($this->getReference(CampusFixtures::CAMPUS . '_' . $faker->numberBetween(0, 3), Campus::class))
                ->setOrganizer($this->getReference('user_' . $faker->numberBetween(0, 9), User::class))
            ;
            $manager->persist($event);
        }

        $manager->flush();

    }
}