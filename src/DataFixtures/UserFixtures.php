<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CampusFixtures::class];
    }
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 30; $i++) {
            $user = new User();
            $user->setPassword($this->passwordHasher->hashPassword($user, '12345678A!'))
                ->setName($faker->lastname())
                ->setFirstName($faker->firstName())
                ->setEmail(strtolower($user->getFirstName()) . '.' . strtolower($user->getName()) . '@campus-eni.fr')
                ->setPhoneNumber($faker->phoneNumber())
                ->setIsAdmin($faker->boolean())
                ->setIsActive($faker->boolean())
                ->setIsVerified(true)
                ->setCampus($this->getReference(CampusFixtures::CAMPUS . '_' . $faker->numberBetween(0, 3), Campus::class))
            ;

            $manager->persist($user);
            $this->addReference('user_' . $i, $user);

        }

        $manager->flush();
    }
}
