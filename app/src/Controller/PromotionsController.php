<?php

namespace App\Controller;

use App\Entity\Promotions;
use App\Form\PromotionsType;
use App\Repository\PromotionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/promotions')]
#[IsGranted('ROLE_USER')]
final class PromotionsController extends AbstractController
{
    #[Route(name: 'app_promotions_index', methods: ['GET'])]
    public function index(PromotionsRepository $promotionsRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VISITOR')) {
            $data = $promotionsRepository->findAll();
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $data = $promotionsRepository->findByTeacher($user);
        } else {
            $data = $promotionsRepository->findByStudent($user);
        }

        $promotions = $paginator->paginate($data, $request->query->getInt('page', 1), 20);

        return $this->render('promotions/index.html.twig', [
            'promotions' => $promotions,
        ]);
    }

    #[Route('/new', name: 'app_promotions_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promotion = new Promotions();
        $form = $this->createForm(PromotionsType::class, $promotion);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promotion);
            $entityManager->flush();

            return $this->redirectToRoute('app_promotions_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('promotions/new.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_promotions_show', methods: ['GET'])]
    public function show(Promotions $promotion): Response
    {
        return $this->render('promotions/show.html.twig', [
            'promotion' => $promotion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_promotions_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Request $request, Promotions $promotion, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $promotion->getProfessor()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres promotions.');
        }

        $form = $this->createForm(PromotionsType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_promotions_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('promotions/edit.html.twig', [
            'promotion' => $promotion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_promotions_delete', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(Request $request, Promotions $promotion, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $promotion->getProfessor()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres promotions.');
        }

        if ($this->isCsrfTokenValid('delete' . $promotion->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($promotion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_promotions_index', [], Response::HTTP_SEE_OTHER);
    }
}
