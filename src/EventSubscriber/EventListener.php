<?php

namespace App\EventSubscriber;

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

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['app_main', 'app_login', 'app_logout'])) {
            return;
        }

        $today = new \DateTime();
        $today->setTime((int)$today->format('H'), (int)$today->format('i'), 0);

        /*
        $states = [
            'closed' => $this->stateRepository->find(3),
            'current' => $this->stateRepository->find(4),
            'ended' => $this->stateRepository->find(5),
            'archived' => $this->stateRepository->find(7),
        ];
        */

        // Etat Cloturée
        $eventsToClose = $this->sortieRepository->findEventsToClose($today);
        if (!empty($eventsToClose)) {
            foreach ($eventsToClose as $eventToClose) {
                if ($eventToClose->getState()->getId() !== 3) {
                    $eventToClose->setState(3);
                    $this->entityManager->persist($eventToClose);
                }
            }
            $this->entityManager->flush();
        }

        // Etat En cours
        $eventsToOpen = $this->sortieRepository->findEventsToOpen($today);
        if (!empty($eventsToOpen)) {
            foreach ($eventsToOpen as $eventToOpen) {
                if ($eventToOpen->getState()->getId() !== 4) {
                    $eventToOpen->setState(4);
                    $this->entityManager->persist($eventToOpen);
                }
            }
            $this->entityManager->flush();
        }

        // Etat Passée
        $eventsToEnd = $this->sortieRepository->findEventsToEnd($today);
        if (!empty($eventsToEnd)) {
            foreach ($eventsToEnd as $eventToEnd) {
                if ($eventToEnd->getState()->getId() !== 5) {
                    $eventToEnd->setState(5);
                    $this->entityManager->persist($eventToEnd);
                }
            }
            $this->entityManager->flush();
        }

        // Etat Archivée
        $eventsToArchive = $this->sortieRepository->findEventsToArchive($today);
        if (!empty($eventsToArchive)) {
            foreach ($eventsToArchive as $eventToArchive) {
                if ($eventToArchive->getState()->getId() !== 7) {
                    $eventToArchive->setState(7);
                    $this->entityManager->persist($eventToArchive);
                }
            }
            $this->entityManager->flush();
        }

    }
}
