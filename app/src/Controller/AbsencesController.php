<?php

namespace App\Controller;

use App\Entity\Absences;
use App\Form\AbsencesType;
use App\Repository\AbsencesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/absences')]
#[IsGranted('ROLE_USER')]
final class AbsencesController extends AbstractController
{
    #[Route(name: 'app_absences_index', methods: ['GET'])]
    public function index(AbsencesRepository $absencesRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VISITOR')) {
            $data = $absencesRepository->findAll();
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $data = $absencesRepository->findByTeacher($user);
        } else {
            $data = $absencesRepository->findByStudent($user);
        }

        $raw = $request->query->get('page', '');
        $page = ($raw !== '' && ctype_digit($raw)) ? (int) $raw : 1;

        $absences = $paginator->paginate($data, $page, 20);

        return $this->render('absences/index.html.twig', [
            'absences' => $absences,
        ]);
    }

    #[Route('/new', name: 'app_absences_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $absence = new Absences();
        $form = $this->createForm(AbsencesType::class, $absence, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($absence);
            $entityManager->flush();

            return $this->redirectToRoute('app_absences_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('absences/new.html.twig', [
            'absence' => $absence,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_absences_show', methods: ['GET'])]
    public function show(Absences $absence, AbsencesRepository $absencesRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_ADMIN')) {
            if (!$absencesRepository->isTeacherAllowed($user, $absence)) {
                throw $this->createAccessDeniedException();
            }
        } elseif (!$this->isGranted('ROLE_TEACHER') && $absence->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('absences/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_absences_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Request $request, Absences $absence, EntityManagerInterface $entityManager, AbsencesRepository $absencesRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$absencesRepository->isTeacherAllowed($this->getUser(), $absence)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AbsencesType::class, $absence, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_absences_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('absences/edit.html.twig', [
            'absence' => $absence,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_absences_delete', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(Request $request, Absences $absence, EntityManagerInterface $entityManager, AbsencesRepository $absencesRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$absencesRepository->isTeacherAllowed($this->getUser(), $absence)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $absence->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($absence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_absences_index', [], Response::HTTP_SEE_OTHER);
    }
}
