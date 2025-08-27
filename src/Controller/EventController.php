<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Helper\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event', name: 'event')]
final class EventController extends AbstractController
{

    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em, ParameterBagInterface $parameterBag, FileUploader $fileUploader, Security $security): Response
    {
        $event = new Event();


        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        $start = $event->getStartingDateHour();
        $end = $event->getEndDateHour();

        if ($form->isSubmitted() && $form->isValid()) {



            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {
                $name = $fileUploader->upload($file, $event->getName(), $parameterBag->get('event')['poster_file']);
                $event->setPosterFile($name);
            }

            $interval = $start->diff($end);
            $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            $event->setDuration($minutes);


            $user = $this->getUser();
            $event->setOrganizer($user);


            $place = $form->get('place')->getData();
            $event->setPlace($place);


            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created!');
            return $this->redirectToRoute('app_main');
        }
        return $this->render('event/create.html.twig', [
            'event_form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, ParameterBagInterface $parameterBag, FileUploader $fileUploader, Security $security): Response
    {
        if($event->getOrganizer() !== $security->getUser()){
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cet évènement");
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {
            /*$file = $form->get('poster_file')->getData();

            if($file instanceof UploadedFile){
                $dir = $parameterBag->get('event')['poster_dir'];
                $name = $fileUploader->upload($file, $event->getName(), $dir);

                $event->setPosterFile($name);

                if ($event->getPosterFile() && file_exists($dir . '/' . $event->getPosterFile()){
                    unlink($dir . '/' . $event->getPosterFile());
                    }
                    $event->setPosterFile($name);
            }
            $event->setPosterFile($name);*/

            $em->flush();
            $this->addFlash('success', 'Event edited!');
            return $this->redirectToRoute('event_list', ['id' => $event->getId()]);
        }
        return $this->render('event/create.html.twig', [
            'event_form' => $form,
        ]);
    }

    #[Route('/list{page}',
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
    public function detail(SortieRepository $sortieRepository, int $id, ParameterBagInterface $bag): Response
    {
        $event = $sortieRepository->find($id);

        if (!$event){
            throw $this->createNotFoundException('Cet évènement n\'existe pas');
        }

        return $this->render('event/detail.html.twig', [
            'id' => $id,
            'event' =>$event
        ]);
    }

    #[Route ('/cancel/{id}', name: '_cancel', requirements: ['id' => '\d+'])]
    public function cancel( Event $event, EntityManagerInterface $em, Security $security, SortieRepository $sortieRepository ):Response
    {
        if($event->getOrganizer() !== $security->getUser()){
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cet évènement");
        }

        $cancel = $sortieRepository->findBy(['name' => 'Annulée']);
        if(!$cancel){
            throw $this->createNotFoundException('statut introuvable !');
        }

        $event->setState($cancel);

    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        if($event->getOrganizer() !== $security->getUser()){
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cet évènement");
        }
        if($this->isCsrfTokenValid('delete'.$event->getId(), $request->get('token'))) {
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Event deleted!');
        }else{
            $this->addFlash('danger', 'Suppression impossible !');

        }
        return $this->redirectToRoute('event_list');
    }

}