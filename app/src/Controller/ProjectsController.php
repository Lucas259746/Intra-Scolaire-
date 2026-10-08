<?php

namespace App\Controller;

use App\Entity\Grades;
use App\Entity\Projects;
use App\Enum\GradeStatus;
use App\Form\ProjectsType;
use App\Form\GradeSubmission;
use App\Repository\GradesRepository;
use App\Repository\ProjectsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
#[IsGranted('ROLE_USER')]
final class ProjectsController extends AbstractController
{
    #[Route(name: 'app_projects_index', methods: ['GET'])]
    public function index(ProjectsRepository $projectsRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VISITOR')) {
            $data = $projectsRepository->findAll();
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $data = $projectsRepository->findByTeacher($user);
        } else {
            $data = $projectsRepository->findByStudent($user);
        }

        $raw  = $request->query->get('page', '');
        $page = ($raw !== '' && ctype_digit($raw)) ? (int) $raw : 1;

        $projects = $paginator->paginate($data, $page, 20);

        return $this->render('projects/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'app_projects_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Projects();
        $form    = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('app_projects_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('projects/new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_projects_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Projects $project, GradesRepository $gradesRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $grades = $gradesRepository->findBy(
            ['project' => $project],
            ['status' => 'ASC']
        );


        $grade = $gradesRepository->findOneBy([
            'project' => $project,
            'student' => $this->getUser(),
        ]);

        if (!$grade && $this->isGranted('ROLE_STUDENT')) {

            $user = $this->getUser();
            $userPromotions = $user->getPromotionUsers()->map(fn($pu) => $pu->getPromotion());
            $projectPromotions = $project->getPromotions();

            $matchingPromotion = $userPromotions->filter(function ($promo) use ($projectPromotions) {
                return $projectPromotions->contains($promo);
            })->first();

            if ($matchingPromotion) {
                $grade = new Grades();
                $grade->setProject($project);
                $grade->setPromotion($matchingPromotion);
                $grade->setStudent($this->getUser());
                $grade->setStatus(GradeStatus::PENDING);
                $grade->setUpdateHistory(new DateTime());
                $entityManager->persist($grade);
                $entityManager->flush();
            }
        }

        if (!$this->isGranted('ROLE_TEACHER') && !$project->isVisibility()) {
            throw $this->createAccessDeniedException();
        }

        $form    = null;

        if ($grade && $grade->getStatus() === GradeStatus::PENDING) {
            $form = $this->createForm(GradeSubmission::class, $grade, [
                'action' => $this->generateUrl('app_projects_show', ['id' => $project->getId()]),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $grade->setStatus(GradeStatus::SUBMITTED);
                $grade->setUpdateHistory(new DateTime());
                $entityManager->flush();
                $this->addFlash('success', 'Projet soumis avec succès.');

                return $this->redirectToRoute('app_projects_show', ['id' => $project->getId()]);
            }
        }

        $grades = $this->isGranted('ROLE_TEACHER')
            ? $gradesRepository->findBy(['project' => $project], ['status' => 'ASC'])
            : [];

        return $this->render('projects/show.html.twig', [
            'project'        => $project,
            'grades'         => $grades,
            'myGrade'        => $grade,
            'submissionForm' => $form?->createView(),
        ]);
    }


    #[Route('/{id}/toggle', name: 'app_projects_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function toggle(Request $request, Projects $project, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle' . $project->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $wasHidden = !$project->isVisibility();
        $project->setVisibility(!$project->isVisibility());

        if ($wasHidden) {
            $gradesRepo = $entityManager->getRepository(Grades::class);

            foreach ($project->getPromotions() as $promotion) {
                foreach ($promotion->getPromotionUsers() as $pu) {
                    $student = $pu->getUser();
                    if (!in_array('ROLE_STUDENT', $student->getRoles(), true)) {
                        continue;
                    }

                    $existing = $gradesRepo->findOneBy(['project' => $project, 'student' => $student]);
                    if (!$existing) {
                        $grade = new Grades();
                        $grade->setProject($project);
                        $grade->setStudent($student);
                        $grade->setStatus(GradeStatus::PENDING);
                        $grade->setUpdateHistory(new DateTime());
                        $entityManager->persist($grade);
                    }
                }
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_projects_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/sendback/{gradeId}', name: 'app_projects_sendback', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function sendback(Request $request, Projects $project, int $gradeId, GradesRepository $gradesRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('sendback' . $gradeId, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $grade = $gradesRepository->find($gradeId);

        if (!$grade || $grade->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $grade->setStatus(GradeStatus::PENDING);
        $grade->setSubmission(null);
        $grade->setUpdateHistory(new DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Rendu renvoyé à l\'étudiant.');

        return $this->redirectToRoute('app_projects_show', ['id' => $project->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_projects_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Request $request, Projects $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_projects_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('projects/edit.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_projects_delete', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(Request $request, Projects $project, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_projects_index', [], Response::HTTP_SEE_OTHER);
    }
}
