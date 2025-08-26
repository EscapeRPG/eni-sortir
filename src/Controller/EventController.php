<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Place;
use App\Form\EventType;
use App\Helper\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/event', name: 'app_event')]
    public function index(): Response
    {
        return $this->render('event/create.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }


    #[Route('/event/create', name: 'event_create')]
    public function create(Request $request, EntityManagerInterface $em, ParameterBagInterface $parameterBag, FileUploader $fileUploader): Response
    {
        $event = new Event();


        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        $start = $event->getStartingDateHour();
        $end = $event->getEndDateHour();

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {
                $name = $fileUploader->upload($file,$event->getName(),$parameterBag->get('event')['poster_file']);
                    $event->setPosterFile($name);
            }

            $interval = $start->diff($end);
            $minutes = ($interval->days * 24 * 60) + ($interval->h * 60)+$interval->i;
            $event->setDuration($minutes);

            $user = $this->getUser();
            $event->setOrganizer($user);


            $place = $form->get('place')->getData();
            $event->setPlace($place);


            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created!');
            return $this->redirectToRoute('app_event');
        }
        return $this->render('event/create.html.twig', [
            'event_form'=>$form,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit', requirements : ['id'=> '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()){
            $em->flush();
            $this->addFlash('success', 'Event edited!');
            return $this->redirectToRoute('app_main', ['id'=>$event->getId()]);
        }
        return $this->render('event/create.html.twig', [
            'event_form'=>$form,
        ]);
    }

}