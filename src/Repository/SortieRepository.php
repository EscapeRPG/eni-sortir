<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findParticipantsByEvent(int $id): array
    {

        $sql = <<<SQL

SELECT u.profil_picture, u.id, u.first_name FROM USER u JOIN user_event ue ON u.ID = ue.user_id WHERE ue.event_id =:id              

SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['id' => $id])
            ->fetchAllAssociative();
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

    public function findEventsToClose(\DateTime $today): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.state', 's')
            ->addSelect('s')
            ->where('e.registrationDeadline <= :today')
            ->andWhere('s.id != :closedId AND s.id < :closedId')
            ->setParameter('today', $today)
            ->setParameter('closedId', 3)
            ->getQuery()
            ->getResult();
    }

    public function findEventsToArchive(\DateTime $today): array
    {
        $archiveDate = (clone $today)->modify('-1 month');

        return $this->createQueryBuilder('e')
            ->join('e.state', 's')
            ->addSelect('s')
            ->where('e.endDateHour <= :archiveDate')
            ->andWhere('s.id != :archivedId AND s.id < :archivedId')
            ->setParameter('archiveDate', $archiveDate)
            ->setParameter('archivedId', 7)
            ->getQuery()
            ->getResult();
    }

    public function findEventsToOpen(\DateTime $today): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.state', 's')
            ->addSelect('s')
            ->where('e.startingDateHour <= :today')
            ->andWhere('s.id != :currentId AND s.id < :currentId')
            ->setParameter('today', $today)
            ->setParameter('currentId', 4)
            ->getQuery()
            ->getResult();
    }

    public function findEventsToEnd(\DateTime $today): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.state', 's')
            ->addSelect('s')
            ->where('e.endDateHour <= :today')
            ->andWhere('s.id != :endedId AND s.id < :endedId')
            ->setParameter('today', $today)
            ->setParameter('endedId', 5)
            ->getQuery()
            ->getResult();
    }

    public function findEventsByFilters($campus, $name, $startingDay, $endingDay, $organizer, $subscribed, $notSubscribed, $passedEvents, $limit, $offset): Paginator
    {
        $req = $this->createQueryBuilder('e')
            ->orderBy('e.startingDateHour', 'ASC')
            ->leftJoin('e.place', 'place')
            ->addSelect('place')
            ->leftJoin('e.organizer', 'organizer')
            ->addSelect('organizer')
            ->leftJoin('e.state', 'state')
            ->addSelect('state')
            ->andWhere('e.state != 1')
            ->andWhere('e.state != 7');

        if (!empty($campus)) {
            $req->andWhere('e.campus = :campus')
                ->setParameter(':campus', $campus);
        }

        if (!empty($name)) {
            $req->andWhere('e.name LIKE :name')
                ->setParameter(':name', '%' . $name . '%');
        }

        if ($startingDay !== null) {
            $req->andWhere('e.startingDateHour >= :startingDay')
                ->setParameter(':startingDay', $startingDay);
        }

        if ($endingDay !== null) {
            $req->andWhere('e.endDateHour <= :endingDay')
                ->setParameter(':endingDay', $endingDay);
        }

        if ($organizer !== null) {
            $req->andWhere('e.organizer = :organizer')
                ->setParameter(':organizer', $organizer);
        }

        if ($passedEvents !== null) {
            $req->andWhere('e.state = 5');
        }

        if ($subscribed !== null) {
            $req->join('e.participants', 'participants')
                ->andWhere('participants = :subscribed')
                ->setParameter(':subscribed', $subscribed);
        }

        if ($notSubscribed !== null) {
            $subQuery = $this->createQueryBuilder('e2')
                ->select('e2.id')
                ->join('e2.participants', 'not_subscribed')
                ->where('not_subscribed = :notSubscribed')
                ->getDQL();

            $req->andWhere($req->expr()->notIn('e.id', $subQuery))
                ->setParameter(':notSubscribed', $notSubscribed);
        }

        $req->setFirstResult($offset)
            ->setMaxResults($limit);

        $query = $req->getQuery();

        return new Paginator($query);
    }

    public function findActiveEvents(array $excludedStates): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.state NOT IN (:excludedStates)')
            ->setParameter('excludedStates', $excludedStates)
            ->orderBy('e.startingDateHour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEventsByStateApi(int $stateId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.state = :stateId')
            ->setParameter('stateId', $stateId)
            ->orderBy('e.startingDateHour', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
