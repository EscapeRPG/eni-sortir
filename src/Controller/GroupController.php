<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\User;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/group', name: 'group')]
final class GroupController extends AbstractController
{

    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em, #[CurrentUser]?User $userConnecter): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($userConnecter) {
                $group->addUserList($userConnecter);
            }

            $em->persist($group);
            $em->flush();

            $this->addFlash('success', 'Groupe créé avec succès !');

            return $this->redirectToRoute('group_list');
        }

        return $this->render('group/group.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @throws Exception
     */
    #[Route('/list', name: '_list',requirements: ['page' => '\d+'])]
    public function list(GroupRepository $groupRepository, #[CurrentUser] ?user $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $userId = $user->getId();

        $groups = $groupRepository->findAllMyGroups($userId);

               return $this->render('group/list.html.twig', [
                   'groups' => $groups,
               ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(GroupRepository $groupRepository,int $id, #[CurrentUser] $userConnected) : Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $groupDetails = $groupRepository->findGroupDetails($id);

        if(!$groupDetails){
            $this->addFlash('error','Ce groupe n\'existe pas');
            return $this->redirectToRoute('group_list');
        }

        if (!$groupDetails->getUserList()->contains($userConnected)) {
            $this->addFlash('error', 'Vous ne faites pas partie de ce groupe');
            return $this->redirectToRoute('group_list');
        }

        return $this->render('group/detail.html.twig', [
            'groupDetails' => $groupDetails,
            'id' => $id
        ]);
    }

    // src/Controller/GroupController.php

    /**
     * @throws Exception
     */
    #[Route('/members-count/{id}', name: '_members_count', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function countGroupMembers(int $id, GroupRepository $groupRepository): Int
    {
        $listUsers = $groupRepository->findGroupUsers($id);

        return count($listUsers);
    }

}
