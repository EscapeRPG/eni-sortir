<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * @throws Exception
     */
    public function findAllMyGroups(int $userId): array
    {

        $sql = <<<SQL

SELECT g.name, g.id FROM `group` g JOIN GROUP_USER gu ON g.id = gu.group_id WHERE gu.user_id =:id              

SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['id' => $userId])
            ->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function findGroupDetails(int $groupId): array
    {
        $sql = <<<SQL
SELECT 
    g.id,
    g.name AS group_name,
    e.name AS event_name,
    u.first_name,
    u.name AS last_name,
    u.profil_picture AS profil_picture

FROM `group` g
LEFT JOIN group_user gu ON gu.group_id = g.id
LEFT JOIN user u ON gu.user_id = u.id
LEFT JOIN event e ON g.id = e.group_id
WHERE g.id = :group_id
SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['group_id' => $groupId])
            ->fetchAllAssociative();

    }

    /**
     * @throws Exception
     */
    public function findGroupUsers(int $groupId): array
    {
        $sql = <<<SQL
SELECT 
    u.name AS last_name
FROM `group` g
LEFT JOIN group_user gu ON gu.group_id = g.id
LEFT JOIN user u ON gu.user_id = u.id
LEFT JOIN event e ON g.id = e.group_id
WHERE g.id = :group_id
SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['group_id' => $groupId])
            ->fetchAllAssociative();

    }


    public function findGroupsOfUserConnected(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder('g')
            ->join('g.userList', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('g.name', 'ASC');
    }

}
