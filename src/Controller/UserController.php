<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function editProfile(UserRepository $userRepository,int $id, Request $request, EntityManagerInterface $em): Response
    {

        $userConnected = $this->getUser();

        if ($userConnected->getUserIdentifier() !== $userRepository->findUserById('id')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette page');

        } else {
            $form = $this->createForm(RegistrationFormType::class, $userRepository);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $id = $form->getData()->getId();
                $em->flush();

                $this->addFlash('success', "Mise à jour enregistrée");

                return $this->redirectToRoute('app_main', ['id' => $id]);
            }

            return $this->render('user/edit.html.twig', [
                'edit_form' => $form,
                'user' => $userConnected,
            ]);
        }
    }

}
