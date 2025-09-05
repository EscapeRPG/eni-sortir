<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\State;
use App\Entity\User;
use App\Form\EditType;
use App\Form\RegistrationFormType;
use App\Form\UserCsvImportType;
use App\Form\UserFilterType;
use App\Helper\FileUploader;
use App\Repository\UserRepository;
use App\Service\UserCsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/update/{id}', name: 'app_update', requirements: ['id' => '\d+'])]
    public function editProfile(
        UserRepository         $userRepository,
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User   $userConnected,
        ParameterBagInterface  $parameterBag,
        FileUploader           $fileUploader,
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $userRepository->findUserById($id);

        if ($userConnected === $user || in_array('ROLE_ADMIN', $userConnected->getRoles(), true)) {
            $isSelfEdit = $this->getUser() === $user;
            $form = $this->createForm(EditType::class, $user, [
                'is_admin' => $this->isGranted('ROLE_ADMIN'),
                'is_self_edit' => $isSelfEdit,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $file = $form->get('profilPicture')->getData();

                if ($file instanceof UploadedFile) {
                    $name = $fileUploader->upload(
                        $file,
                        $user->getName(),
                        $parameterBag->get('user')['profil_picture']
                    );

                    $user->setProfilPicture($name);
                }
                $em->flush();

                $this->addFlash('success', "Mise à jour enregistrée");

                if ($user->isAdmin() === true) {
                    return $this->redirectToRoute('app_users_list');
                } else {
                    return $this->redirectToRoute('app_update', ['id' => $id]);
                }
            }

            return $this->render('user/edit.html.twig', [
                'edit_form' => $form,
                'user' => $user,
                'id' => $id
            ]);

        } else {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
        }
        return $this->redirectToRoute('app_main');
    }

    #[Route('/users/list/{page}', name: 'app_users_list', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function usersList(
        UserCsvImporter $userCsvImporter,
        Request $request,
        UserRepository $userRepository,
        #[CurrentUser] ?User $userConnected,
        ParameterBagInterface $bag,
        EntityManagerInterface $em,
        int $page
    ): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
            {
                return $redirect;
            }

        $form = $this->createForm(UserCsvImportType::class);
        $form->handleRequest($request);

        $logs = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csv_file')->getData();

            if (!$file) {
                $this->addFlash('error', 'Le formulaire n\'est pas valide');
            } else {
                $logs = $userCsvImporter->import($file->getPathname());

                foreach ($logs as $log) {
                    if (str_contains(strtolower($log), 'ignorée') || str_contains(strtolower($log), 'inconnu') || str_contains(strtolower($log), 'erreur')) {
                        $this->addFlash('error', $log);
                    } else {
                        $this->addFlash('success', $log);
                    }
                }

            }

            return $this->redirectToRoute('app_users_list');
        }

        $limit = $bag->get('user')['nb_max'];
        $offset = ($page - 1) * $limit;

        $campusId = $request->query->get('campus') ? $request->query->get('campus') : 0;

        $filterForm = $this->createForm(UserFilterType::class, [
            'campus' => $campusId ? $em->getReference(Campus::class, $campusId) : null,
        ], ['method' => 'POST']);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
            $users = [
                'page' => 1
            ];

            if ($filters['campus'] != null) {
                $users['campus'] = $filters['campus']->getId();
            }

            return $this->redirectToRoute('app_users_list', $users);
        }

        $users = $userRepository->findUsersByFilter($campusId, $offset, $limit);
        $totalItems = count($users);
        $pages = ceil($totalItems / $limit);

        return $this->render('user/users_list.html.twig', [
            'users' => $users,
            'import_form' => $form->createView(),
            'message' => $logs,
            'page' => $page,
            'pages' => $pages,
            'filters' => $filterForm
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('users/delete/{id}', name: 'app_users_delete', requirements: ['id' => '\d+'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected, MailerInterface $mailer): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
        {
            return $redirect;
        };

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $cancelState = $em->getRepository(State::class)->findOneBy(['label' => 'Annulée']);

            foreach ($user->getMyEvents() as $event) {
                $event->setState($cancelState);
                $event->setCancellationReason('Le profil de l\'utilisateur à été supprimé');
                $event->setOrganizer(null);//pour enlever la dépendance à un event sinon ca ne veut pas
                $em->persist($event);
            }
            $em->flush();

            $email = (new Email())
                ->from('postmaster@syrphin.com') // @TODO à changer en fonction déploiement si on le fait
                ->to($user->getEmail())
                ->subject('Suppression de compte utilisateur')
                ->html($this->renderView('email/deleteUser.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);

            $em->remove($user);

            $em->flush();

            $this->addFlash('success', "L'utilisateur a été supprimé");



        } else {
            $this->addFlash('success', "Impossible de supprimer l'utilisateur");
        }
        return $this->redirectToRoute('app_users_list');
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('users/disable/{id}', name: 'app_users_disable', requirements: ['id' => '\d+'])]
    public function disableUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected, MailerInterface $mailer): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
        {
            return $redirect;
        };

        //partie ajoutée pour supprimer les event si l'uti est desac
        if ($user->isActive() === true) {
            $user->setIsActive(false);

            $cancelState = $em->getRepository(State::class)->findOneBy(['label' => 'Annulée']);
            foreach ($user->getMyEvents() as $event) {
                $event->setState($cancelState);
                $event->setCancellationReason('Le profil de l\'utilisateur à été désactivé');
                $em->persist($event);
            }

            $em->flush();
            $em->persist($user);

            $email = (new Email())
                ->from('postmaster@syrphin.com') // @TODO à changer en fonction déploiement si on le fait
                ->to($user->getEmail())
                ->subject('Désactivation de compte utilisateur')
                ->html($this->renderView('email/desactivate.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);

            $this->addFlash('success', "L'utilisateur a été désactivé");

        } else {
            $user->setIsActive(true);

            $activeState = $em->getRepository(State::class)->findOneBy(['label' => 'Ouverte']);
            foreach ($user->getMyEvents() as $event) {
                $event->setState($activeState);
                $em->persist($event);
            }

            $em->flush();
            $em->persist($user);

            $email = (new Email())
                ->from('postmaster@syrphin.com') // @TODO à changer en fonction déploiement si on le fait
                ->to($user->getEmail())
                ->subject('Activation de compte utilisateur')
                ->html($this->renderView('email/activate.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);

            $this->addFlash('success', "L'utilisateur a été activé");
        }

        return $this->redirectToRoute('app_update', ['id' => $user->getId()]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('users/promote/{id}', name: 'app_users_promote', requirements: ['id' => '\d+'])]
    public function promoteUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected, MailerInterface $mailer): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
        {
            return $redirect;
        };

        if ($user->isAdmin() === false) {
            $user->setIsAdmin(true);
            $user->setRoles(['ROLE_ADMIN']);
            $em->flush();
            $em->persist($user);

            $email = (new Email())
                ->from('postmaster@syrphin.com') // @TODO à changer en fonction déploiement si on le fait
                ->to($user->getEmail())
                ->subject('Nouveau rôle : administrateur d\'ENI-SORTIR')
                ->html($this->renderView('email/promote.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);

            $this->addFlash('success', "L'utilisateur est maintenant administrateur");

        } else {
            $user->setIsAdmin(false);
            $user->setRoles(['ROLE_USER']);
            $em->flush();

            $email = (new Email())
                ->from('postmaster@syrphin.com') // @TODO à changer en fonction déploiement si on le fait
                ->to($user->getEmail())
                ->subject('Rétrogradation de votre rôle d\'administrateur d\'ENI-SORTIR')
                ->html($this->renderView('email/downgrade.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);

            $em->persist($user);

            $this->addFlash('success', "L'utilisateur n'est plus administrateur");
        }

        return $this->redirectToRoute('app_update', ['id' => $user->getId()]);
    }

    #[Route('profile/{id}', name: 'app_profile', requirements: ['id' => '\d+'])]
    public function profile(int $id, UserRepository $userRepository, #[CurrentUser] $userConnected): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $participant = $userRepository->find($id);
        $events = $participant->getEvents();
        $organizer = $participant->getMyEvents();

        $uniqueDates = [];
        foreach ($events as $event) {
            $formattedDate = $event->getStartingDateHour()->format('d/m/Y');
            $uniqueDates[$formattedDate] = $formattedDate;
        }
        $uniqueDates = array_values(array_unique($uniqueDates));

        return $this->render('user/profile.html.twig', [
            'participant' => $participant,
            'events' => $events,
            'organizer' => $organizer,
            'eventsDates' => $uniqueDates,
        ]);
    }

    #[Route('/preferences/{id}', name: 'app_preferences', requirements: ['id' => '\d+'])]
    public function preferences(
        #[CurrentUser] ?User   $userConnected,
        ParameterBagInterface  $parameterBag,
        UserRepository         $userRepository,
        FileUploader           $fileUploader,
        int                    $id,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $userRepository->findUserById($id);

        if ($userConnected === $user) {
            $isSelfEdit = $this->getUser() === $user;
            $form = $this->createForm(EditType::class, $user, [
                'is_admin' => $this->isGranted('ROLE_ADMIN'),
                'is_self_edit' => $isSelfEdit,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $file = $form->get('profilPicture')->getData();

                if ($file instanceof UploadedFile) {
                    $name = $fileUploader->upload(
                        $file,
                        $user->getName(),
                        $parameterBag->get('user')['profil_picture']
                    );

                    $user->setProfilPicture($name);
                }
                $em->flush();

                $this->addFlash('success', "Mise à jour enregistrée");

                return $this->redirectToRoute('app_preferences', ['id' => $id]);
            }

        } else {
            $this->addFlash('error','Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }
        return $this->render('user/preferences.html.twig', [
            'edit_form' => $form,
            'user' => $user,
            'id' => $id
        ]);

    }

    private function checkUserAdmin(#[CurrentUser] ?User $userConnected): ?Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('error','Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }
        return null;
    }

}

