<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\State;
use App\Entity\User;
use App\Form\EventType;
use App\Helper\FileUploader;
use App\Repository\StateRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
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
            $event->addUser($user);
            $event->setOrganizer($user);
            $event->setCampus($user->getCampus());

            $place = $form->get('place')->getData();
            $event->setPlace($place);

            if ($form->get('saveDraft')->isClicked()) {
                $state = $em->getRepository(State::class)->find(1);
            } elseif ($form->get('publish')->isClicked()) {
                $state = $em->getRepository(State::class)->find(2);
            }

            $event->setState($state);

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
        if ($event->getOrganizer() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
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
    public function index(SortieRepository $sortieRepository, ParameterBagInterface $bag, int $page, #[CurrentUser] ?User $user): Response
    {
        $limit = $bag->get('event')['nb_max'];
        $offset = ($page - 1) * $limit;
        $campus = $user->getCampus()->getId();
        $events = $sortieRepository->findAllEvents($limit, $offset, $campus);
        $pages = ceil($events->count() / $limit);
        $dates = $sortieRepository->findEventsDates($limit, $offset, $campus);

        $uniqueDates = [];

        foreach ($dates as $date) {
            $formattedDate = $date['startingDateHour']->format('d/m/Y');
            $uniqueDates[$formattedDate] = $formattedDate;
        }

        $uniqueDates = array_unique($uniqueDates);
        $uniqueDates = array_values($uniqueDates);

        return $this->render('event/list.html.twig', [
            'eventsDates' => $uniqueDates,
            'events' => $events,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(SortieRepository $sortieRepository, int $id, ParameterBagInterface $bag): Response
    {
        $event = $sortieRepository->find($id);


        if (!$event) {
            throw $this->createNotFoundException('Cet évènement n\'existe pas');
        }

        return $this->render('event/detail.html.twig', [
            'id' => $id,

        $listParticipants = $sortieRepository->findParticipantsByEvent($event->getId());

        return $this->render('event/detail.html.twig', [
            'id' => $id,
            'event' =>$event,
            'participants' => $listParticipants,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/close/{id}', name: '_close', requirements: ['id' => '\d+'])]
    public function close(StateRepository $stateRepository, SortieRepository $sortieRepository, int $id, ParameterBagInterface $bag, EntityManagerInterface $entityManager): Response
    {
        $event = $sortieRepository->find($id);
        $listParticpants = $sortieRepository->findParticipantsByEvent($event->getId());

        $nbParticipants = count($listParticpants);
        $nbmaxParticipants = $event->getNbInscriptionsMax();

        if ($nbmaxParticipants == $nbParticipants) {
            $closureState = $stateRepository->find(3);
            $event->setState($closureState);
            $entityManager->persist($event);
            $entityManager->flush();
        }

    }


    /**
     * @throws Exception
     */
    #[Route('/join/{id}', name: '_join', requirements: ['id' => '\d+'])]
    public function join(SortieRepository $sortieRepository, #[CurrentUser] ?User $userConnected, int $id, ParameterBagInterface $bag, EntityManagerInterface $entityManager): Response {

        $event = $sortieRepository->find($id);
        $listParticipants = $sortieRepository->findParticipantsByEvent($event->getId());

        if ($event->getState()->getId() !== 2 ) {
            throw $this->createAccessDeniedException("Tu ne peux pas t'inscrire à cet évènement");
        }

        $nbParticipants = count($listParticipants);
        $nbmaxParticipants = $event->getNbInscriptionsMax();

        if ($nbmaxParticipants >= $nbParticipants){
            $event->addUser($userConnected);
            $entityManager->persist($event);
            $entityManager->flush();

        }

        return $this->redirectToRoute('event_list', [
            'participants' => $listParticipants,
            'event' => $event
        ]);
    }

    #[Route ('/cancel/{id}', name: '_cancel', requirements: ['id' => '\d+'])]
    public function cancel(Event $event, EntityManagerInterface $em, Security $security, StateRepository $stateRepository): Response
    {
        if ($event->getOrganizer() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cet évènement (ou admin)");
        }

        $cancel = $stateRepository->findOneBy(['label' => 'Annulée']);
        if (!$cancel) {
            throw $this->createNotFoundException('statut introuvable !');
        }

        $event->setState($cancel);
        $em->flush();
        $this->addFlash('success', 'Event annulé !');

        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }

        #[Route ('/reactivate/{id}', name: '_reactivate', requirements: ['id' => '\d+'])]
        public function reactivate(Event $event, EntityManagerInterface $em, Security $security,StateRepository $stateRepository): Response
        {
            if ($event->getOrganizer() !== $security->getUser()&& !$security->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cet évènement");
            }
            $reac = $stateRepository->findOneBy(['label' => 'Créée']);
            if (!$reac) {
                throw $this->createNotFoundException('statut introuvable !');
            }
            $event->setState($reac);
            $em->flush();
            $this->addFlash('success','Event réactivé !');

            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);


        }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        if($event->getOrganizer() !== $security->getUser()&& !$security->isGranted('ROLE_ADMIN')){
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