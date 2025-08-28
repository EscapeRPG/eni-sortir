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
    public function findParticipantsByEvent(int $id): array
    {

        $sql = <<<SQL
SELECT u.first_name, u.name FROM USER u JOIN user_event ue ON u.ID = ue.user_id WHERE ue.event_id =:id              
SQL;
        $stmt = $this->getEntityManager()->getConnection();
        return $stmt->prepare($sql)
            ->executeQuery(['id' => $id])
            ->fetchAllAssociative();
    }

    public function findAllEvents(int $limit, int $offset, string $campus): Paginator
    {
        $events = $this->createQueryBuilder('e')
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

    public function findEventsByFilters($campus, $name, $startingDay, $endingDay, $organizer, $subscribed, $notSubscribed, $passedEvents, $limit, $offset): Paginator
    {
        $req = $this->createQueryBuilder('e')
            ->orderBy('e.startingDateHour', 'ASC');

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
}
