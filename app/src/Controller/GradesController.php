<?php

namespace App\Controller;

use App\Entity\Grades;
use App\Form\GradesType;
use App\Form\GradeCorrectType;
use App\Enum\GradeStatus;
use App\Repository\GradesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/grades')]
#[IsGranted('ROLE_USER')]
final class GradesController extends AbstractController
{
    #[Route(name: 'app_grades_index', methods: ['GET'])]
    public function index(GradesRepository $gradesRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VISITOR')) {
            $data = $gradesRepository->findAll();
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $data = $gradesRepository->findSubmittedByTeacher($user);
        } else {
            $data = $gradesRepository->findByStudent($user);
        }

        $raw = $request->query->get('page', '');
        $page = ($raw !== '' && ctype_digit($raw)) ? (int) $raw : 1;

        $grades = $paginator->paginate($data, $page, 20);

        return $this->render('grades/index.html.twig', [
            'grades' => $grades,
        ]);
    }

    #[Route('/new', name: 'app_grades_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $grade = new Grades();
        $form = $this->createForm(GradesType::class, $grade, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $grade->setUpdateHistory(new \DateTime());
            $entityManager->persist($grade);
            $entityManager->flush();

            return $this->redirectToRoute('app_grades_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('grades/new.html.twig', [
            'grade' => $grade,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_grades_show', methods: ['GET'])]
    public function show(Grades $grade, GradesRepository $gradesRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_ADMIN')) {
            if (!$gradesRepository->isTeacherAllowed($user, $grade)) {
                throw $this->createAccessDeniedException();
            }
        } elseif (!$this->isGranted('ROLE_TEACHER') && $grade->getStudent() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('grades/show.html.twig', [
            'grade' => $grade,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_grades_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Request $request, Grades $grade, EntityManagerInterface $entityManager, GradesRepository $gradesRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$gradesRepository->isTeacherAllowed($this->getUser(), $grade)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(GradeCorrectType::class, $grade, [
            'action' => $this->generateUrl('app_grades_edit', ['id' => $grade->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $grade->setStatus(GradeStatus::GRADED);
            $grade->setUpdateHistory(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_grades_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('grades/edit.html.twig', [
            'grade' => $grade,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_grades_delete', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(Request $request, Grades $grade, EntityManagerInterface $entityManager, GradesRepository $gradesRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$gradesRepository->isTeacherAllowed($this->getUser(), $grade)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $grade->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($grade);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_grades_index', [], Response::HTTP_SEE_OTHER);
    }
}
