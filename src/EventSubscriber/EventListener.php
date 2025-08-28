<?php
// src/EventSubscriber/CheckEventClosureSubscriber.php

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
        private SortieRepository $sortieRepository,
        private StateRepository $stateRepository,
        private EntityManagerInterface $entityManager
    ) {}

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

        if (in_array($route, ['app_login', 'app_logout', 'app_register', 'app_verify_email', 'app_forgot_password_request', 'app_check_email', 'app_reset_password'])) {
            return;
        }

        $today = new \DateTime();
        $events = $this->sortieRepository->findEventsToClose($today);

        $closureState = $this->stateRepository->find(3);

        foreach ($events as $eventToClose) {
            $eventToClose->setState($closureState);
            $this->entityManager->persist($eventToClose);
        }

        if (count($events) > 0) {
            $this->entityManager->flush();
        }
    }
}
