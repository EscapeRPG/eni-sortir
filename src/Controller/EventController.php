<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/event', name: 'app_event')]
    public function index(): Response
    {
        return $this->render('event/index.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }


    #[Route('/event/create', name: 'event_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) /*&& $form->isValid())*/ {
                dd($event);

            /*$em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created!');
            return $this->redirectToRoute('app_main');
        }*/}
        return $this->render('event/create.html.twig', [
            'event_form'=>$form,
        ]);
    }

}