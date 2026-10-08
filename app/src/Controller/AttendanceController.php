<?php

namespace App\Controller;

use App\Entity\Absences;
use App\Entity\Attendance;
use App\Entity\AttendanceSignature;
use App\Entity\Promotions;
use App\Form\AttendanceSignatureType;
use App\Repository\AbsencesRepository;
use App\Repository\AttendanceRepository;
use App\Repository\AttendanceSignatureRepository;
use App\Enum\AttendanceType;
use App\Repository\PromotionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AttendanceController extends AbstractController
{
    // Liste des promos pour le prof
    #[Route('/attendance', name: 'app_attendance', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function index(PromotionsRepository $promotionsRepository): Response
    {
        return $this->render('attendance/index.html.twig', [
            'promotions' => $promotionsRepository->findBy(['professor' => $this->getUser()]),
        ]);
    }

    // Feuille d'appel détaillée
    #[Route('/attendance/promo/{id}', name: 'app_attendance_sheet', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function sheet(Promotions $promotion, Request $request, AttendanceRepository $attendanceRepo, AttendanceSignatureRepository $signatureRepo, AbsencesRepository $absencesRepo, EntityManagerInterface $entityManager): Response
    {
        // On récupère la date depuis l'URL ou on prend aujourd'hui
        $dateString = $request->query->get('date');
        $date = $dateString ? new \DateTime($dateString) : new \DateTime('today');

        // Calcul des dates pour le menu déroulant (7 derniers jours)
        $availableDates = [];
        for ($i = 0; $i < 7; $i++) {
            $d = (new \DateTime())->modify("-$i days");
            $availableDates[$d->format('Y-m-d')] = $d;
        }

        $students = $promotion->getStudents();

        $isToday = $date->format('Y-m-d') === (new \DateTime('today'))->format('Y-m-d');

        // Récupère les présences existantes pour cette promo et cette date
        $existingAttendances = $attendanceRepo->findTodayAttendanceByPromotion($promotion, $date);
        $attendanceMap = [];
        foreach ($existingAttendances as $att) {
            $attendanceMap[$att->getStudent()->getId()] = $att;
        }

        $pendingSignatures = $signatureRepo->findPendingByPromotionAndDate($promotion, $date);

        // Handle form submission from teacher (save attendance for today)
        if ($request->isMethod('POST')) {
            if (!$isToday) {
                $this->addFlash('warning', "Historique verrouillé, impossible de modifier une date passée.");
                return $this->redirectToRoute('app_attendance_sheet', ['id' => $promotion->getId(), 'date' => $date->format('Y-m-d')]);
            }

            $attendanceData = $request->request->all('attendance', []);
            if (!is_array($attendanceData)) {
                $attendanceData = [];
            }

            $attendanceDate = (clone $date)->setTime(0, 0, 0);
            $now = new \DateTime();

            foreach ($students as $student) {
                $sid = $student->getId();
                $statusStr = $attendanceData[$sid] ?? null;
                if ($statusStr === null) {
                    continue;
                }

                try {
                    $statusEnum = AttendanceType::from($statusStr);
                } catch (\ValueError $e) {
                    $statusEnum = AttendanceType::PRESENT;
                }

                $existingAbsence = $absencesRepo->findOneByStudentAndDate($student, $attendanceDate);

                if (isset($attendanceMap[$sid])) {
                    $att = $attendanceMap[$sid];
                    $att->setStatus($statusEnum);
                    $att->setSignedAt($now);
                } else {
                    $att = new Attendance();
                    $att->setStudent($student);
                    $att->setPromotion($promotion);
                    $att->setDate($attendanceDate);
                    $att->setStatus($statusEnum);
                    $att->setSignedAt($now);
                    $entityManager->persist($att);
                }

                if ($statusEnum === AttendanceType::ABSENT) {
                    if (!$existingAbsence) {
                        $absence = new Absences();
                        $absence->setUser($student);
                        $absence->setStartDate($now);
                        $absence->setEndDate(null);

                        // ==========================================
                        // AJOUT 1 : Transfert du document (Appel général)
                        // ==========================================
                        $studentSignature = $signatureRepo->findOneByStudentAndDate($student, $attendanceDate);
                        if ($studentSignature && $studentSignature->getDocument()) {
                            $absence->setDocument($studentSignature->getDocument());
                        }
                        // ==========================================

                        $entityManager->persist($absence);
                    }
                } elseif ($existingAbsence) {
                    $entityManager->remove($existingAbsence);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Présences enregistrées.');
            return $this->redirectToRoute('app_attendance_sheet', ['id' => $promotion->getId(), 'date' => $date->format('Y-m-d')]);
        }

        return $this->render('attendance/sheet.html.twig', [
            'promotion' => $promotion,
            'students' => $students,
            'date' => $date,
            'isToday' => $isToday,
            'availableDates' => $availableDates,
            'attendanceMap' => $attendanceMap,
            'pendingSignatures' => $pendingSignatures,
        ]);
    }

    #[Route('/attendance/signature/{id}/validate', name: 'app_attendance_signature_validate', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function validateSignature(AttendanceSignature $pendingSignature, Request $request, AttendanceRepository $attendanceRepo, AbsencesRepository $absencesRepo, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('validate_signature' . $pendingSignature->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_attendance_sheet', ['id' => $pendingSignature->getPromotion()->getId(), 'date' => $pendingSignature->getDate()->format('Y-m-d')]);
        }

        $status = $request->request->get('status');
        if (!in_array($status, ['present', 'late', 'absent'], true)) {
            $this->addFlash('danger', 'Statut invalide pour la validation.');
            return $this->redirectToRoute('app_attendance_sheet', ['id' => $pendingSignature->getPromotion()->getId(), 'date' => $pendingSignature->getDate()->format('Y-m-d')]);
        }

        $student = $pendingSignature->getStudent();
        $date = $pendingSignature->getDate();
        $promotion = $pendingSignature->getPromotion();

        $statusEnum = AttendanceType::from($status);
        $attendanceDate = new \DateTime($date->format('Y-m-d'));
        $now = new \DateTime();

        $existingAttendance = $attendanceRepo->findOneByStudentAndDate($student, $attendanceDate);
        if ($existingAttendance) {
            $existingAttendance->setStatus($statusEnum);
            $existingAttendance->setComment($pendingSignature->getComment());
            $existingAttendance->setSignedAt($now);
        } else {
            $existingAttendance = new Attendance();
            $existingAttendance->setStudent($student);
            $existingAttendance->setPromotion($promotion);
            $existingAttendance->setDate($attendanceDate);
            $existingAttendance->setStatus($statusEnum);
            $existingAttendance->setComment($pendingSignature->getComment());
            $existingAttendance->setSignedAt($now);
            $entityManager->persist($existingAttendance);
        }

        $existingAbsence = $absencesRepo->findOneByStudentAndDate($student, $attendanceDate);
        if ($statusEnum === AttendanceType::ABSENT) {
            if (!$existingAbsence) {
                $absence = new Absences();
                $absence->setUser($student);
                $absence->setStartDate($now);
                $absence->setEndDate(null);

                // ==========================================
                // AJOUT 2 : Transfert du document (Action de ligne)
                // ==========================================
                if ($pendingSignature->getDocument()) {
                    $absence->setDocument($pendingSignature->getDocument());
                }
                // ==========================================

                $entityManager->persist($absence);
            }
        } elseif ($existingAbsence) {
            $entityManager->remove($existingAbsence);
        }

        $entityManager->remove($pendingSignature);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Demande de %s validée.', $status === 'present' ? 'présence' : 'absence'));

        return $this->redirectToRoute('app_attendance_sheet', ['id' => $promotion->getId(), 'date' => $date->format('Y-m-d')]);
    }

    // Signature pour les étudiants
    #[Route('/attendance/signer', name: 'app_attendance_sign', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function sign(Request $request, AttendanceRepository $attendanceRepo, AttendanceSignatureRepository $signatureRepo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_TEACHER') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException("Cette page est réservée aux étudiants.");
        }

        $user = $this->getUser();
        $attendance = $attendanceRepo->findOneByStudentAndDate($user, new \DateTime('today'));
        $pendingSignature = $signatureRepo->findOneByStudentAndDate($user, new \DateTime('today'));

        // Si pas de signature en attente mais statut final validé, afficher le message
        if (!$pendingSignature) {
            $isFinalStatus = $attendance && !in_array($attendance->getStatus(), [AttendanceType::PENDING_PRESENT, AttendanceType::PENDING_ABSENT, AttendanceType::PENDING_LATE], true);
            if ($isFinalStatus) {
                return $this->render('attendance/signature.html.twig', [
                    'form' => null,
                    'infoMessage' => 'Votre présence a déjà été validée par le professeur.',
                    'attendance' => $attendance,
                ]);
            }

            // Créer une nouvelle signature
            $pendingSignature = new AttendanceSignature();
            $pendingSignature->setDate((new \DateTime('today'))->setTime(0, 0, 0));

            $promotion = null;
            foreach ($user->getPromotionUsers() as $promotionUser) {
                if ($promotionUser->getPromotion()) {
                    $promotion = $promotionUser->getPromotion();
                    break;
                }
            }

            if (!$promotion) {
                return $this->render('attendance/signature.html.twig', [
                    'form' => null,
                    'errorMessage' => 'Vous n\'êtes rattaché(e) à aucune promotion. Contactez un professeur.',
                ]);
            }

            $pendingSignature->setPromotion($promotion);
            $pendingSignature->setStudent($user);
        }

        $form = $this->createForm(AttendanceSignatureType::class, $pendingSignature, [
            'initial_status' => $pendingSignature->getStatus()?->value,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Debug logs to trace submission issues
            try {
                $this->container->get('logger')->info('Attendance sign submitted', ['request' => $request->request->all()]);
            } catch (\Throwable $e) {
                // ignore logging failure
            }

            if ($form->isValid()) {
                $statusRaw = $form->get('status')->getData();
                try {
                    $pendingSignature->setStatus(AttendanceType::from($statusRaw));
                } catch (\ValueError $e) {
                    $pendingSignature->setStatus(AttendanceType::PENDING_PRESENT);
                }

                $pendingSignature->setSignedAt(new \DateTime());

                // Le document s'enregistre TOUT SEUL maintenant grâce au mapping automatique !
                $entityManager->persist($pendingSignature);
                $entityManager->flush();

                $this->addFlash('success', 'Votre demande de présence est envoyée.');
                return $this->redirectToRoute('app_dashboard');
            } else {
                $messages = [];
                foreach ($form->getErrors(true) as $error) {
                    $messages[] = $error->getMessage();
                }
                $this->addFlash('danger', 'Le formulaire contient des erreurs : ' . implode(' ; ', $messages));
                try {
                    $this->container->get('logger')->warning('Attendance sign form invalid', ['errors' => $messages]);
                } catch (\Throwable $e) {
                }
            }
        }

        return $this->render('attendance/signature.html.twig', [
            'form' => $form,
            'attendance' => $pendingSignature,
        ]);
    }

    #[Route('/attendance/signatures/{id}/reset', name: 'app_attendance_signatures_reset', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function resetSignatures(Promotions $promotion, Request $request, AttendanceRepository $attendanceRepo, AttendanceSignatureRepository $signatureRepo, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reset_signatures' . $promotion->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_attendance_sheet', ['id' => $promotion->getId(), 'date' => (new \DateTime('today'))->format('Y-m-d')]);
        }

        $date = new \DateTime('today');
        $dateStart = (clone $date)->setTime(0, 0, 0);
        $dateEnd = (clone $date)->setTime(23, 59, 59);

        // Supprimer les signatures en attente du jour
        $pendingSignatures = $signatureRepo->findPendingByPromotionAndDate($promotion, $date);
        foreach ($pendingSignatures as $sig) {
            $entityManager->remove($sig);
        }

        // Supprimer les attendances du jour
        $attendances = $attendanceRepo->findTodayAttendanceByPromotion($promotion, $date);
        foreach ($attendances as $att) {
            $entityManager->remove($att);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Les signatures de la journée ont été réinitialisées. Les étudiants peuvent à nouveau signer leur présence.');
        return $this->redirectToRoute('app_attendance_sheet', ['id' => $promotion->getId(), 'date' => $date->format('Y-m-d')]);
    }
}
