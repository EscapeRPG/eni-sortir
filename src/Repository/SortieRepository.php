<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @throws Exception
     */
    public function findNamesParticipantsByEvent(int $id) : array {

        $sql = <<<SQL
SELECT u.first_name, u.name  FROM USER u JOIN user_event ue ON u.ID = ue.user_id WHERE ue.event_id =:id              
SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['id' => $id])
        ->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function findIdParticipantsByEvent(int $id) : array {

        $sql = <<<SQL
SELECT u.id  FROM USER u JOIN user_event ue ON u.ID = ue.user_id WHERE ue.event_id =:id              
SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['id' => $id])
            ->fetchAllAssociative();
    }
    public function findAllEvents(int $limit, int $offset, string $campus): Paginator
    {
         $events=$this->createQueryBuilder('e')
            ->orderBy('e.startingDateHour', 'ASC')
            ->leftJoin('e.place', 'place')
            ->addSelect('place')
            ->leftJoin('e.organizer', 'organizer')
            ->addSelect('organizer')
            ->leftJoin('e.state', 'state')
            ->addSelect('state')
           ->leftJoin('e.participants', 'participants')
            ->addSelect('participants')
            ->andWhere('e.campus = :campus')
            ->setParameter(':campus', $campus)
            ->andWhere('e.state != 1')
            ->andWhere('e.state != 7')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($events);
    }

    public function findEventsDates(int $limit, int $offset, string $campus): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.startingDateHour')
            ->orderBy('e.startingDateHour', 'ASC')
            ->where('e.campus = :campus')
            ->setParameter(':campus', $campus)
            ->andWhere('e.state != 1')
            ->andWhere('e.state != 7')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws Exception
     */
    public function removeParticipant(int $eventId, int $userId): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'DELETE FROM user_event WHERE event_id = :eventId AND user_id = :userId';

        $stmt = $conn->prepare($sql);
        $stmt->executeStatement([
            'eventId' => $eventId,
            'userId' => $userId,
        ]);
    }


    //    /**
    //     * @return Event[] Returns an array of Event objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findEventsToClose(\DateTime $today): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.registrationDeadline <= :today')
            ->andWhere('e.state != :closed')
            ->setParameter('today', $today->setTime(0, 0))
            ->setParameter('closed', 3)
            ->getQuery()
            ->getResult();
    }

}
