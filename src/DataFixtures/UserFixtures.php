<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail())
                ->setPassword($faker->password())
                ->setName($faker->name())
                ->setFirstName($faker->firstName())
                ->setPhoneNumber($faker->phoneNumber())
                ->setIsAdmin($faker->boolean())
                ->setIsActive($faker->boolean())
                ->setCampus($this->getReference(CampusFixtures::CAMPUS, Campus::class));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
