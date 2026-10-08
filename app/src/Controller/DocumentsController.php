<?php

namespace App\Controller;

use App\Entity\Documents;
use App\Form\DocumentsType;
use App\Repository\DocumentsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/documents')]
#[IsGranted('ROLE_USER')]
final class DocumentsController extends AbstractController
{
    /**
     * Index avec Pagination
     */
    #[Route('', name: 'app_documents_index', methods: ['GET'])]
    public function index(DocumentsRepository $documentsRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $qb = $documentsRepository->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC');

        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_VISITOR')) {
            $qb->where('d.user = :user')
                ->setParameter('user', $this->getUser());
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('documents/index.html.twig', [
            'documents' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_documents_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_STUDENT')) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }

        $currentUser = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $userChoices = null;
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $userChoices = $userRepository->findStudents($currentUser);
        } else {
            $userChoices = false;
        }

        $document = new Documents();
        $form = $this->createForm(DocumentsType::class, $document, ['user_choices' => $userChoices]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('attachment')->getData();

            if ($file) {
                $safeFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move($this->getParameter('documents_directory'), $newFilename);
                    $document->setPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur lors de l'upload du fichier.");
                    return $this->render('documents/new.html.twig', [
                        'document' => $document,
                        'form' => $form,
                    ], new Response(null, 422));
                }
            }

            // Sécurité : on force l'utilisateur actuel si non défini dans le formulaire
            if (!$document->getUser()) {
                $document->setUser($this->getUser());
            }

            $entityManager->persist($document);
            $entityManager->flush();

            return $this->redirectToRoute('app_documents_index', [], Response::HTTP_SEE_OTHER);
        }

        // IMPORTANT : Code 422 si le formulaire est invalide pour que Turbo affiche les erreurs
        return $this->render('documents/new.html.twig', [
            'document' => $document,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/{id}/edit', name: 'app_documents_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Documents $document, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_STUDENT')) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }

        if (!$this->isGranted('ROLE_TEACHER') && $document->getUser()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $userChoices = null;
        } elseif ($this->isGranted('ROLE_TEACHER')) {
            $userChoices = $userRepository->findStudents($currentUser);
        } else {
            $userChoices = false;
        }

        $form = $this->createForm(DocumentsType::class, $document, ['user_choices' => $userChoices]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('attachment')->getData();

            if ($file) {
                $safeFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move($this->getParameter('documents_directory'), $newFilename);
                    $document->setPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur lors de la modification.");
                    return $this->render('documents/edit.html.twig', [
                        'document' => $document,
                        'form' => $form,
                    ], new Response(null, 422));
                }
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_documents_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('documents/edit.html.twig', [
            'document' => $document,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/{id}', name: 'app_documents_show', methods: ['GET'])]
    public function show(Documents $document): Response
    {
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_VISITOR') && $document->getUser()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('documents/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}', name: 'app_documents_delete', methods: ['POST'])]

    public function delete(Request $request, Documents $document, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_STUDENT')) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        if (!$this->isGranted('ROLE_TEACHER') && $document->getUser()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $token = $request->request->get('_token');
        if (!$token && method_exists($request, 'getPayload')) {
            $token = $request->getPayload()->getString('_token');
        }

        if ($this->isCsrfTokenValid('delete' . $document->getId(), $token)) {
            $path = $this->getParameter('documents_directory') . '/' . $document->getPath();
            if ($document->getPath() && file_exists($path)) {
                unlink($path);
            }

            $entityManager->remove($document);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_documents_index', [], Response::HTTP_SEE_OTHER);
    }
}
