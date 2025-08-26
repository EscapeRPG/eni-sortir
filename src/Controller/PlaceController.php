<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Place;
use App\Form\EventType;
use App\Form\PlaceType;
use App\Form\PlaceType22;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PlaceController extends AbstractController
{

    #[Route('/place/create', name: 'place_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $place = new Place();


        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($place);
            $em->flush();

            $this->addFlash('success', 'Place created!');
            return $this->redirectToRoute('event_create');
        }
        return $this->render('place/place.html.twig', [
            'place_create' => $form,
        ]);


    }

}
