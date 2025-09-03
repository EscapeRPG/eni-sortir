<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Event;
use App\Entity\Group;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use App\Form\CancellationReasonType;
use App\Form\EventType;
use App\Form\FiltersType;
use App\Form\GroupType;
use App\Form\PlaceType;
use App\Helper\FileUploader;
use App\Message\SendMailReminder;
use App\Repository\GroupRepository;
use App\Repository\StateRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortieRepository;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

use Symfony\Component\Mime\Email;
#[Route('/event', name: 'event')]
final class EventController extends AbstractController
{


    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }
/**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em, ParameterBagInterface $parameterBag, FileUploader $fileUploader, #[CurrentUser] ?User $user, GroupRepository $groupRepository, MailerInterface $mailer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = new Event();
        $place = new Place();
        $group = new Group();

        $placeForm = $this->createForm(PlaceType::class, $place);
        $groupForm = $this->createForm(GroupType::class, $group, []);
        $form = $this->createForm(EventType::class, $event, [
            'user' => $user,
            'group_repository' => $groupRepository,
        ]);

        $form->handleRequest($request);

        $start = $event->getStartingDateHour();
        $end = $event->getEndDateHour();

        if ($form->isSubmitted() && $form->isValid()) {


            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {

                $name = $fileUploader->upload($file, $event->getName(), $parameterBag->get('event')['poster_dir']);
                $event->setPosterFile($name);
            }

            $interval = $start->diff($end);
            $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            $event->setDuration($minutes);
            $event->addUser($user);
            $event->setOrganizer($user);
            $event->setCampus($user->getCampus());

            if ($event->getGroup() != null) {
                $group = $groupRepository->find($event->getGroup());
                $id = $group->getId();
                $listUsers = $groupRepository->findGroupUsers($id);
                $nbParticipantsGroup = count($listUsers);
                $event->setNbInscriptionsMax($nbParticipantsGroup);
            }

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

            if ($event->getGroup() !== null) {
                $group = $event->getGroup();
                $users = $group->getUserList();

                foreach ($users as $member) {
                    if ($member->getId() === $user->getId()) {
                        continue;
                    }

                    $email = (new Email())
                        ->from('no-reply@eni-sortir.com') // @TODO à changer en fonction déploiement si on le fait
                        ->to($member->getEmail())
                        ->subject('Invitation à un nouvel événement : '.$event->getName())
                        ->html($this->renderView('email/invitation.html.twig', [
                            'event' => $event,
                            'user' => $member,
                        ]));

                    $mailer->send($email);
                }
            }

            $this->addFlash('success', 'Événement crée !');
            return $this->redirectToRoute('event_list');
        }
        return $this->render('event/create.html.twig', [
            'event_form' => $form,
            'place_form' => $placeForm->createView(),
            'group_form' => $groupForm->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, ParameterBagInterface $parameterBag, FileUploader $fileUploader, Security $security): Response
    {
        if ($redirect = $this->checkStatusUser($event, $security))
        {
           return $redirect;
        };

        $form = $this->createForm(EventType::class, $event);
        $placeForm = $this->createForm(PlaceType::class, new Place());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('poster_file')->getData();

            if ($file instanceof UploadedFile) {
                $name = $fileUploader->upload($file, $event->getName(), $parameterBag->get('event')['poster_dir']);
                $event->setPosterFile($name);
            }

            //$event->setPosterFile($name);

            $em->flush();
            $this->addFlash('success', 'Événement édité!');
            return $this->redirectToRoute('event_list', ['id' => $event->getId()]);
        }

        return $this->render('event/create.html.twig', [
            'event_form' => $form,
            'place_form' => $placeForm->createView(),
        ]);
    }

    /**
     * @throws ORMException
     * @throws \Exception
     */
    #[Route('/list/{page}', name: '_list', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function list(
        SortieRepository       $sortieRepository,
        ParameterBagInterface  $bag,
        int                    $page,
        #[CurrentUser] ?User   $user,
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $limit = $bag->get('event')['nb_max'];
        $offset = ($page - 1) * $limit;

        $campusId = $request->query->get('campus') ? $request->query->get('campus') : $user->getCampus()->getId();
        $name = $request->query->get('name') ? $request->query->get('name') : null;
        $startingDay = $request->query->get('startingDay') ? new \DateTime($request->query->get('startingDay')) : null;
        $endingDay = $request->query->get('endingDay') ? new \DateTime($request->query->get('endingDay')) : null;
        $organizer = $request->query->get('organizer') ? $request->query->get('organizer') : null;
        $subscribed = $request->query->get('subscribed') ? $request->query->get('subscribed') : null;
        $notSubscribed = $request->query->get('notSubscribed') ? $request->query->get('notSubscribed') : null;
        $passedEvents = $request->query->get('passedEvents') ? $request->query->get('passedEvents') : null;

        $form = $this->createForm(FiltersType::class, [
            'campus' => $campusId ? $entityManager->getReference(Campus::class, $campusId) : $user->getCampus(),
            'name' => $name,
            'startingDay' => $startingDay,
            'endingDay' => $endingDay,
            'organizer' => (bool)$organizer,
            'subscribed' => (bool)$subscribed,
            'notSubscribed' => (bool)$notSubscribed,
            'passedEvents' => (bool)$passedEvents,
        ], ['method' => 'POST']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            $events = [
                'page' => 1,
            ];

            if ($filters['campus']) {
                $events['campus'] = $filters['campus']->getId();
            }
            if (!empty($filters['name'])) {
                $events['name'] = $filters['name'];
            }
            if ($filters['startingDay']) {
                $events['startingDay'] = $filters['startingDay']->format('Y-m-d');
            }
            if ($filters['endingDay']) {
                $events['endingDay'] = $filters['endingDay']->format('Y-m-d');
            }
            if (!empty($filters['organizer'])) {
                $events['organizer'] = $user?->getId();
            }
            if (!empty($filters['subscribed'])) {
                $events['subscribed'] = $user?->getId();
            }
            if (!empty($filters['notSubscribed'])) {
                $events['notSubscribed'] = $user?->getId();
            }
            if (!empty($filters['passedEvents'])) {
                $events['passedEvents'] = true;
            }

            return $this->redirectToRoute('event_list', $events);
        }

        $events = $sortieRepository->findEventsByFilters(
            $campusId,
            $name,
            $startingDay,
            $endingDay,
            $organizer,
            $subscribed,
            $notSubscribed,
            $passedEvents,
            $limit,
            $offset
        );
        $totalItems = count($events);
        $pages = ceil($totalItems / $limit);

        $uniqueDates = [];
        foreach ($events as $event) {
            $formattedDate = $event->getStartingDateHour()->format('d/m/Y');
            $uniqueDates[$formattedDate] = $formattedDate;
        }
        $uniqueDates = array_values(array_unique($uniqueDates));

        return $this->render('event/list.html.twig', [
            'eventsDates' => $uniqueDates,
            'events' => $events,
            'page' => $page,
            'pages' => $pages,
            'filters' => $form
        ]);
    }


    /**
     * @throws Exception
     */
    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(SortieRepository $sortieRepository, int $id, #[CurrentUser] ?User $userConnected): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $sortieRepository->find($id);
        $userConnectedId = $userConnected->getId();


        if (!$event) {
            $this->addFlash('error','Cet évènement n\'existe pas');
            return $this->redirectToRoute('event_list');
        }

        $participants = $sortieRepository->findParticipantsByEvent($event->getId());

        return $this->render('event/detail.html.twig', [
            'id' => $id,
            'event' => $event,
            'participants' => $participants,
            'userConnectedId' => $userConnectedId,
        ]);
    }

    /**
     * @throws Exception
     */

    public function closeIfFullParticipants(StateRepository $stateRepository, SortieRepository $sortieRepository, int $id, EntityManagerInterface $entityManager): void
    {
        $event = $sortieRepository->find($id);
        $participants = $sortieRepository->findParticipantsByEvent($event->getId());

        $nbParticipants = count($participants);
        $nbmaxParticipants = $event->getNbInscriptionsMax();

        if ($nbmaxParticipants == $nbParticipants) {
            $closureState = $stateRepository->find(3);
            $event->setState($closureState);
            $entityManager->persist($event);
            $entityManager->flush();
        }
    }

    #todo non utilisé à supprimer?
    /*public function closeIfOutDate(StateRepository $stateRepository, SortieRepository $sortieRepository, int $id, EntityManagerInterface $entityManager): void
    {
        $event = $sortieRepository->find($id);
        $today = new \DateTime();
        $closureDate = $event->getRegistrationDeadline();
        $closureState = $stateRepository->find(3);

        if ($closureDate == $today) {
            $event->setState($closureState);
            $entityManager->persist($event);
            $entityManager->flush();
        }
    }*/

    /**
     * @throws Exception
     */
    #[Route('/join/{id}', name: '_join', requirements: ['id' => '\d+'])]
    public function join(StateRepository $stateRepository, SortieRepository $sortieRepository, #[CurrentUser] ?User $userConnected, int $id, ParameterBagInterface $bag, EntityManagerInterface $entityManager, MailerInterface $mailer, LoggerInterface $logger, MessageBusInterface $bus): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $sortieRepository->find($id);
        $participants = $sortieRepository->findParticipantsByEvent($event->getId());
        $user= $userConnected->getId();

        if ($event->getState()->getId() !== 2) {
            $this->addFlash('error',"Tu ne peux pas t'inscrire à cet évènement");
            return $this->redirectToRoute('event_list');
        }

        $nbParticipants = count($participants);
        $nbmaxParticipants = $event->getNbInscriptionsMax();

        if ($nbmaxParticipants >= $nbParticipants) {
            $event->addUser($userConnected);
            $entityManager->persist($event);
            $entityManager->flush();


            //insérer l'envoi de mail

            if(!$userConnected->getEmail()) { //si l'adresse n'existe pas ?
                $this->addFlash('danger', 'Impossible d\'envoyer un mail, adresse invalide');
                return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
            }

            $email = (new TemplatedEmail())
                ->from('no-reply@eni-sortir.fr')
                ->to($userConnected->getEmail())
                ->subject('Confirmation d\'inscription à l\'événement ' . $event->getName())
                ->htmlTemplate('email/join.html.twig')
                ->context([
                    'user' => $userConnected,
                    'event' => $event,
                ]);
            try{ //pour ne pas bloquer si l'envoi ne fonctionne pas
                $mailer->send($email);
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Ton inscription est validée mais le mail n\'a pas pu être envoyé');
                $logger->error('mail error : ' .$e->getMessage()); //logger : stock messages dans des fichiers (log)
            }


            $this->addFlash('success', 'Vous êtes inscrit à l\'événement ! Un mail de confirmation va vous être envoyé');

            $this->closeIfFullParticipants($stateRepository, $sortieRepository, $event->getId(), $bag, $entityManager);


            //pour le mail délai 48h !

            $eventId = $event->getId();
            $delay = ($event->getStartingDateHour()->getTimestamp() - 48*3600 - time()) * 1000;
            //timestamp renvoie nbr secondes, puis calcul 48h en sec. puis *1000 car messenger att millisecondes
            $delay = max(0, $delay); // pas  négatif

            $bus->dispatch(new SendMailReminder($eventId), [new DelayStamp($delay)]);
            //si -48h alors on programme le message avec un delaystamp


        }
        return $this->redirectToRoute('event_list', [
            'participants' => $participants,
            'event' => $event
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/withdraw/{id}', name: '_withdraw', requirements: ['id' => '\d+'])]
    public function withdraw(
        SortieRepository       $sortieRepository,
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User   $userConnected,
        int                    $id,
        MailerInterface       $mailer
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $sortieRepository->find($id);
        if (!$event) {
            $this->addFlash('danger', "Événement introuvable.");
            return $this->redirectToRoute('event_list');
        }

        $participants = $sortieRepository->findParticipantsByEvent($event->getId());

        $found = false;
        foreach ($participants as $Idparticipant) {

            if ($Idparticipant['id'] === $userConnected->getId()) {

                $sortieRepository->removeParticipant($event->getId(), $userConnected->getId());

                $entityManager->persist($event);
                $entityManager->flush();


                //mail de désistement

                $email = (new TemplatedEmail())
                    ->from('no-reply@eni-sortir.fr')
                    ->to($userConnected->getEmail())
                    ->subject('Confirmation de désinscription à l\'événement ' . $event->getName())
                    ->htmlTemplate('email/withdraw.html.twig')
                    ->context([
                        'user' => $userConnected,
                        'event' => $event,
                    ]);
                $mailer->send($email);


                $this->addFlash('success', 'Vous vous êtes désinscrit de l\'événement. Un mail de conformation va vous être envoyé');

                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->addFlash('danger', "Tu n'es pas inscrit à cet événement");
        }

        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }


    #[Route ('/cancel/{id}', name: '_cancel', requirements: ['id' => '\d+'])]
    public function cancel(Event $event, EntityManagerInterface $em, Security $security, StateRepository $stateRepository, Request $request): Response
    {
        if ($redirect = $this->checkStatusUser($event, $security))
        {
            return $redirect;
        };

        $form = $this->createForm(CancellationReasonType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cancel = $stateRepository->findOneBy(['label' => 'Annulée']);
            if (!$cancel) {
                throw $this->createNotFoundException('statut introuvable !');
            }

            $event->setState($cancel);
            $em->flush();
            $this->addFlash('success', 'Event annulé !');

            return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
        }
        return $this->render('event/cancel.html.twig', [
            'cancel_form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route ('/reactivate/{id}', name: '_reactivate', requirements: ['id' => '\d+'])]
    public function reactivate(Event $event, EntityManagerInterface $em, Security $security, StateRepository $stateRepository): Response
    {
        if ($redirect = $this->checkStatusUser($event, $security))
        {
            return $redirect;
        };

        $reac = $stateRepository->findOneBy(['label' => 'Ouverte']);
        if (!$reac) {
            throw $this->createNotFoundException('statut introuvable !');
        }
        $event->setState($reac);
        $em->flush();
        $this->addFlash('success', 'Event réactivé !');

        return $this->redirectToRoute('event_detail', ['id' => $event->getId()]);
    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        if ($redirect = $this->checkStatusUser($event, $security))
        {
            return $redirect;
        };

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->get('token'))) {

            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Événement supprimé !');
        } else {
            $this->addFlash('danger', 'Suppression impossible !');

        }
        return $this->redirectToRoute('event_list');
    }

    private function checkStatusUser(Event $event, Security $security): Response
    {
        if ($event->getOrganizer() !== $security->getUser() && !$security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error',"Accès interdit");
            return $this->redirectToRoute('app_main');
        }
    }

}