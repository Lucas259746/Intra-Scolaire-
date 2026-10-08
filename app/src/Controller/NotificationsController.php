<?php

namespace App\Controller;

use App\Entity\Notifications;
use App\Entity\NotificationRecipients;
use App\Form\NotificationsType;
use App\Repository\NotificationRecipientsRepository;
use App\Repository\NotificationsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
final class NotificationsController extends AbstractController
{
    #[Route(name: 'app_notifications_index', methods: ['GET'])]
    public function index(NotificationRecipientsRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $recipients = $paginator->paginate(
            $repo->findByUser($this->getUser()),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('notifications/index.html.twig', [
            'recipients' => $recipients,
        ]);
    }

    #[Route('/{id}/read', name: 'app_notifications_read', methods: ['POST'])]
    public function markRead(
        NotificationRecipients $recipient,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if (!$this->isCsrfTokenValid('read' . $recipient->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($recipient->getUserId()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $recipient->setIsRead(true);
        $recipient->setReadAt(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('app_notifications_index');
    }

    #[Route('/new', name: 'app_notifications_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $notification = new Notifications();
        $currentUser  = $this->getUser();
        $form = $this->createForm(NotificationsType::class, $notification, [
            'current_user' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $audience  = $form->get('audience')->getData();
            $promotion = $form->get('promotion')->getData();

            // Enseignants limités à leurs propres promotions
            if (!$this->isGranted('ROLE_ADMIN')) {
                if ($audience !== 'promotion') {
                    throw $this->createAccessDeniedException();
                }
                if ($promotion !== null && $promotion->getProfessor()?->getId() !== $currentUser->getId()) {
                    throw $this->createAccessDeniedException();
                }
            }
            $notification->setSender($currentUser);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($notification);

            $recipients = match ($audience) {
                'students'  => $userRepository->findByRole('ROLE_STUDENT'),
                'teachers'  => $userRepository->findByRole('ROLE_TEACHER'),
                'promotion' => $promotion
                    ? array_map(fn($pu) => $pu->getUser(), $promotion->getpromotionUsers()->toArray())
                    : [],
                default => $userRepository->findAll(),
            };

            foreach ($recipients as $user) {
                $recipient = new NotificationRecipients();
                $recipient->setNotificationId($notification);
                $recipient->setUserId($user);
                $recipient->setIsRead(false);
                $entityManager->persist($recipient);
            }

            if (!in_array($currentUser, $recipients, true)) {
                $senderEntry = new NotificationRecipients();
                $senderEntry->setNotificationId($notification);
                $senderEntry->setUserId($currentUser);
                $senderEntry->setIsRead(true);
                $entityManager->persist($senderEntry);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_notifications_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notifications/new.html.twig', [
            'notification' => $notification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_notifications_show', methods: ['GET'])]
    public function show(Notifications $notification, NotificationRecipientsRepository $repo): Response
    {
        $recipient = $repo->findOneBy(['notification' => $notification, 'user' => $this->getUser()]);

        if (!$recipient) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('notifications/show.html.twig', [
            'notification' => $notification,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_notifications_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Request $request, Notifications $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getSender()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(NotificationsType::class, $notification, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_notifications_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('notifications/edit.html.twig', [
            'notification' => $notification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_notifications_delete', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(Request $request, Notifications $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getSender()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$notification->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($notification);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_notifications_index', [], Response::HTTP_SEE_OTHER);
    }
}
