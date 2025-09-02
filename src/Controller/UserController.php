<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\State;
use App\Entity\User;
use App\Form\EditType;
use App\Form\RegistrationFormType;
use App\Form\UserCsvImportType;
use App\Helper\FileUploader;
use App\Repository\UserRepository;
use App\Service\UserCsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/users/list', name: 'app_users_list')]
    public function usersList(UserCsvImporter $userCsvImporter, Request $request,UserRepository $userRepository, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
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

        $users = $userRepository->findUsersByCampus($userConnected->getCampus());
        return $this->render('user/users_list.html.twig', [
            'users' => $users,
            'import_form' => $form->createView(),
            'message' => $logs,
        ]);
    }

    #[Route('users/delete/{id}', name: 'app_users_delete', requirements: ['id' => '\d+'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            $cancelState = $em->getRepository(State::class)->findOneBy(['label' => 'Annulée']);

            foreach ($user->getMyEvents() as $event) {
                $event->setState($cancelState);
                $event->setCancellationReason('Le profil de l\'utilisateur à été supprimé');
                $event->setOrganizer(null);//pour enlever la dépendance à un event sinon ca ne veut pas
                $em->persist($event);
            }
            $em->flush();

            $em->remove($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a été supprimé");

        } else {
            $this->addFlash('success', "Impossible de supprimer l'utilisateur");
        }
        return $this->redirectToRoute('app_users_list');
    }

    #[Route('users/disable/{id}', name: 'app_users_disable', requirements: ['id' => '\d+'])]
    public function disableUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }

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

            $this->addFlash('success', "L'utilisateur a été activé");
        }

        return $this->redirectToRoute('app_update', ['id' => $user->getId()]);
    }

    #[Route('users/promote/{id}', name: 'app_users_promote', requirements: ['id' => '\d+'])]
    public function promoteUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }

        if ($user->isAdmin() === false) {
            $user->setIsAdmin(true);
            $em->flush();
            $em->persist($user);

            $this->addFlash('success', "L'utilisateur est maintenant administrateur");

        } else {
            $user->setIsAdmin(false);
            $em->flush();
            $em->persist($user);

            $this->addFlash('success', "L'utilisateur n'est plus administrateur");
        }

        return $this->redirectToRoute('app_update', ['id' => $user->getId()]);
    }

    #[Route('profile/{id}', name: 'app_profile', requirements: ['id' => '\d+'])]
    public function profile(int $id, UserRepository $userRepository): Response
    {
        $participant = $userRepository->find($id);
        $events = $participant->getEvents();
        $organizer = $participant->getMyEvents();
        return $this->render('user/profile.html.twig', [
            'participant' => $participant,
            'events' => $events,
            'organizer' => $organizer,
        ]);
    }

    #[Route('/preferences/{id}', name: 'app_preferences', requirements: ['id' => '\d+'])]
    public function preferences(#[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected) {
            $this->addFlash('error', 'Vous devez être connecté pour consulter cette page');
            return $this->redirectToRoute('app_main');
        }

        return $this->render('user/preferences.html.twig', [
            'user' => $userConnected
        ]);
    }
}
