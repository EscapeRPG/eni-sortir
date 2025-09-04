<?php

namespace App\Controller;


use App\Entity\Place;
use App\Entity\User;
use App\Form\PlaceType;
use App\Repository\PlaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;


#[Route('/place', name: 'place')]
final class PlaceController extends AbstractController
{
    #[Route('/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($place);
            $em->flush();

            $this->addFlash('success', 'Lieu crée !');
            return $this->redirectToRoute('event_create');
        }
        return $this->render('place/place.html.twig', [
            'place_create' => $form->createView(),
        ]);

    }

    #[Route('/create/modal', name: '_createInModal')]
    public function createInModal(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $referer = $request->headers->get('referer');
        if (!$referer || !str_contains($referer, '/event/create')) {
            $this->addFlash('error','Accès interdit');
            return $this->redirectToRoute('event_create');
        }

        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($place);
            $em->flush();

            return $this->json([
                'id' => $place->getId(),
                'name' => $place->getName(),
            ]);
        }

        return $this->json([
            'errors' => (string) $form->getErrors(true, false),
        ], 400);
    }

    #[Route('/list/{page}', name: '_list',requirements: ['page' => '\d+'],defaults: ['page' => 1])]
    public function list(PlaceRepository $placeRepository, ParameterBagInterface $param, int $page, #[CurrentUser] ?user $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $nbParPage = $param->get('place')['nb_max'];
        $offset = ($page - 1) * $nbParPage;
        $campus = $user->getCampus()->getId();

        $places = $placeRepository->findAllPlaces($nbParPage, $offset);
        $pages = ceil($places->count() / $nbParPage);

       return $this->render('place/list.html.twig', [
           'places' => $places,
           'page' => $page,
           'pages' => $pages,
       ]);
    }

    #[Route('/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(PlaceRepository $placeRepository,int $id, ParameterBagInterface $param): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $place = $placeRepository->find($id);

        if(!$place){
            throw $this->createNotFoundException('Cet endroit n\'existe pas');
        }

        return $this->render('place/detail.html.twig', [
            'place' => $place,
            'id' => $id
        ]);
    }

    #[Route('/edit/{id}', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(Place $place, Request $request, EntityManagerInterface $em, #[CurrentUser] $userConnected): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
            {
                return $redirect;
            };

        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
        $em->flush();
        $this->addFlash('success', 'Lieu mis à jour !');
        return $this->redirectToRoute('place_list', ['id' => $place->getId()]);

        }
        return $this->render('place/place.html.twig', [
            'place_create' => $form->createView(),
            'place' => $place,
        ]);
    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
public function delete(Place $place, Request $request, EntityManagerInterface $em, #[CurrentUser] $userConnected): Response
    {
        if ($redirect = $this->checkUserAdmin($userConnected))
        {
            return $redirect;
        };

        if($this->isCsrfTokenValid('delete'.$place->getId(), $request->get('token'))){
        $this->addFlash('danger', 'impossible de supprimer');
        return $this->redirectToRoute('place_list');
        }

        if(count($place->getEvents())>0){
            $this-> addFlash('danger','suppression impossible, ce lieu est lié à un événement');
            return $this->redirectToRoute('place_list');
        }
        $em->remove($place);
        $em->flush();
        $this->addFlash('success', 'lieu supprimé');
        return $this->redirectToRoute('place_list');
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
