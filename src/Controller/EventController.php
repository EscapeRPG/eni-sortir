<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event', name: 'event')]
final class EventController extends AbstractController
{
    #[Route('/list/{page}',
        name: '_list',
        requirements: ['page' => '\d+'],
        defaults: ['page' => 1])]
    public function index(SortieRepository $sortieRepository, ParameterBagInterface $bag, int $page): Response
    {
        $limit = $bag->get('event')['nb_max'];
        $offset = ($page - 1) * $limit;

        // $events = $sortieRepository->findAll();
        $events = $sortieRepository->findAllEvents($limit, $offset);

        $pages = ceil($events->count() / $limit);

        foreach ($events as $eniEvent) {
            $duration = $eniEvent->getStartingDateHour()->diff($eniEvent->getEndDateHour())->format('%d jours %H heures %i minutes %s secondes');
        }

        return $this->render('event/list.html.twig', [
            'events' => $events,
            'duration' => $duration,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(SortieRepository $sortieRepository, int $id, ParameterBagInterface $bag): Response {
        return $this->render('event/detail.html.twig', []);
    }
}
