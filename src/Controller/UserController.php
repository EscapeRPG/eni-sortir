<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/profile/{id}', name: 'app_profile', requirements: ['id' => '\d+'])]
    public function editProfile(
        UserRepository         $userRepository,
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User   $userConnected
    ): Response
    {
        $user = $userRepository->findUserById($id);

        if ($userConnected !== $user) {
            throw $this->createAccessDeniedException('Accès refusé');

        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', "Mise à jour enregistrée");
            return $this->redirectToRoute('event_list');
        }

        return $this->render('user/edit.html.twig', [
            'edit_form' => $form->createView(),
            'user' => $user,
            'id' => $id
        ]);
    }

    #[Route('/users/list', name: 'app_users_list')]
    public function usersList(UserRepository $userRepository, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }

        $users = $userRepository->findUsersByCampus($userConnected->getCampus());
        return $this->render('user/users_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('users/delete/{id}', name: 'app_users_delete', requirements: ['id' => '\d+'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
            $this->addFlash('success', 'Cette page est réservée aux administrateurs');
            return $this->redirectToRoute('app_main');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a été supprimé");

        }else{
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

    if($user->isActive() === true) {
        $user->setIsActive(false);
        $em->flush();
        $em->persist($user);

        $this->addFlash('success', "L'utilisateur a été désactivé");

    }else{
        $user->setIsActive(true);
        $em->flush();
        $em->persist($user);

        $this->addFlash('success', "L'utilisateur a été activé");
    }

    return $this->redirectToRoute('app_users_list');
    }

    #[Route('users/promote/{id}', name: 'app_users_promote', requirements: ['id' => '\d+'])]
    public function promoteUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $userConnected): Response
    {
        if (!$userConnected || !in_array('ROLE_ADMIN', $userConnected->getRoles())) {
        $this->addFlash('success', 'Cette page est réservée aux administrateurs');
        return $this->redirectToRoute('app_main');
        }

        if($user->isAdmin() === false) {
            $user->setIsAdmin(true);
            $em->flush();
            $em->persist($user);

            $this->addFlash('success', "L'utilisateur est maintenant administrateur");

        }else{
            $user->setIsAdmin(false);
            $em->flush();
            $em->persist($user);

            $this->addFlash('success', "L'utilisateur n'est plus administrateur");
        }

        return $this->redirectToRoute('app_users_list');
    }


}
