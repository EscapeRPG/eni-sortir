<?php

namespace App\Command;

use App\Entity\Campus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:import-user-csv',
    description: "Import d'utilisateurs depuis un fichier CSV",
)]
class ImportUserCsvCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csvFile', InputArgument::REQUIRED, 'Chemin vers le fichier CSV');
    }

    private function normalizeEmail(string $email): string
    {
        $email = trim(strtolower($email));
        $domain = '@campus-eni.fr';

        if (!str_ends_with($email, $domain)) {
            $pos = strpos($email, '@');
            if ($pos !== false) {
                $email = substr($email, 0, $pos);
            }
            $email .= $domain;
        }

        return $email;
    }

    private function getOrCreateUser(string $email, array $data = [], ?Campus $campus = null): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'defaultPassword123'));
            $this->em->persist($user);
        }

        if (!empty($data['name'])) {
            $user->setName($data['name']);
        }
        if (!empty($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (!empty($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }
        if (isset($data['isAdmin'])) {
            $user->setIsAdmin((bool)$data['isAdmin']);
        }
        if (isset($data['isActive'])) {
            $user->setIsActive((bool)$data['isActive']);
        }
        if ($campus !== null) {
            $user->setCampus($campus);
        }

        return $user;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('csvFile');

        if (!file_exists($filePath)) {
            $output->writeln("<error>Fichier non trouvé : $filePath</error>");
            return Command::FAILURE;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        $count = 0;

        foreach ($records as $row) {
            if (empty($row['email'])) {
                $output->writeln("<comment>Ligne ignorée : email manquant</comment>");
                continue;
            }

            $email = $this->normalizeEmail($row['email']);
            $campusName = mb_strtoupper(trim($row['campus_name'] ?? ''));

            $campus = null;
            if ($campusName !== '') {
                $campus = $this->em->getRepository(Campus::class)->findOneBy(['name' => $campusName]);

                if (!$campus) {
                    $output->writeln("<comment>Campus inconnu : $campusName, ligne ignorée.</comment>");
                    continue;
                }
            }

            $userData = [
                'name' => trim($row['name'] ?? ''),
                'firstName' => trim($row['first_name'] ?? ''),
                'phoneNumber' => trim($row['phone_number'] ?? ''),
                'isAdmin' => isset($row['is_admin']) ? filter_var($row['is_admin'], FILTER_VALIDATE_BOOLEAN) : false,
                'isActive' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            ];

            $user = $this->getOrCreateUser($email, $userData, $campus);
            $output->writeln("Utilisateur importé/mis à jour : $email");
            $count++;
        }

        $this->em->flush();

        $output->writeln("<info>Import terminé : $count utilisateurs importés ou mis à jour.</info>");

        return Command::SUCCESS;
    }
}
