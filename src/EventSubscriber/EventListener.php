<?php

namespace App\EventSubscriber;

use App\Entity\State;
use App\Repository\SortieRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SortieRepository       $sortieRepository,
        private readonly StateRepository        $stateRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['app_main', 'app_login', 'app_logout'])) {
            return;
        }

        $today = new \DateTime();
        $today->setTime((int)$today->format('H'), (int)$today->format('i'), 0);

        $states = [
            'closed'   => $this->stateRepository->find(3),
            'current'  => $this->stateRepository->find(4),
            'ended'    => $this->stateRepository->find(5),
            'archived' => $this->stateRepository->find(7),
        ];

        // Etat Cloturée
        $eventsToClose = $this->sortieRepository->findEventsToClose($today);
        foreach ($eventsToClose as $eventToClose) {
            if ($eventToClose->getState()->getId() !== $states['closed']->getId()) {
                $eventToClose->setState($states['closed']);
                $this->entityManager->persist($eventToClose);

            }
        }

        // Etat En cours
        $eventsToOpen = $this->sortieRepository->findEventsToOpen($today);
        foreach ($eventsToOpen as $eventToOpen) {
            if ($eventToOpen->getState()->getId() !== $states['current']->getId()) {
                $eventToOpen->setState($states['current']);
                $this->entityManager->persist($eventToOpen);

            }
        }

        // Etat Passée
        $eventsToEnd = $this->sortieRepository->findEventsToEnd($today);
        foreach ($eventsToEnd as $eventToEnd) {
            if ($eventToEnd->getState()->getId() !== $states['ended']->getId()) {
                $eventToEnd->setState($states['ended']);
                $this->entityManager->persist($eventToEnd);

            }
        }

        // Etat Archivée
        $eventsToArchive = $this->sortieRepository->findEventsToArchive($today);
        foreach ($eventsToArchive as $eventToArchive) {
            if ($eventToArchive->getState()->getId() !== $states['archived']->getId()) {
                $eventToArchive->setState($states['archived']);
                $this->entityManager->persist($eventToArchive);
                 }
        }
        $this->entityManager->flush();
    }
}
