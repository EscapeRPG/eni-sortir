<?php
namespace App\Service;

use App\Entity\Campus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCsvImporter
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $passwordHasher,) {}

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

    public function import(string $filePath): array
    {
    $logs = [];

        if(!file_exists($filePath)) {
            $logs[] = "Fichier non trouvé: $filePath";
            return $logs;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        $line = 1;
        foreach ($records as $row) {
            $line ++;
            if (empty($row['email'])) {
                $logs[] = ("Email manquant : ligne $line ignorée ");
                continue;
            }

            $email = $this->normalizeEmail($row['email']);
            $campusName = mb_strtoupper(trim($row['campus_name'] ?? ''));

            $campus = null;
            if ($campusName !== '') {
                $campus = $this->em->getRepository(Campus::class)->findOneBy(['name' => $campusName]);

                if (!$campus) {
                    $logs[] = ("Campus inconnu : $campusName, ligne $line ignorée.");
                    continue;
                }
            }

            $userData = [
                'name' => trim($row['name'] !== '' ? $row['name'] : null),
                'firstName' => trim($row['first_name'] !== '' ? $row['first_name'] : null),
                'phoneNumber' => trim($row['phone_number'] !== '' ? $row['phone_number'] : null),
                'isAdmin' => isset($row['is_admin']) ? filter_var($row['is_admin'], FILTER_VALIDATE_BOOLEAN) : false,
                'isActive' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            ];

            $this->getOrCreateUser($email, $userData, $campus);
            $logs[] = ("Utilisateur importé/mis à jour : $email");
        }

        $this->em->flush();

        $logs[] = ("Import terminé ");

        return $logs;
    }

}