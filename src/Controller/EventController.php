<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event', name: 'event')]
final class EventController extends AbstractController
{
    #[Route('/list', name: '_list')]
    public function index(): Response
    {
        return $this->render('event/list.html.twig', [
        ]);
    }
}
