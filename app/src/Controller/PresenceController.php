<?php

namespace App\Controller;

use App\Entity\Presence;
use App\Form\PresenceSignatureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/presence')]
#[IsGranted('ROLE_USER')]
final class PresenceController extends AbstractController
{
    #[Route('/signer', name: 'app_presence_sign', methods: ['GET', 'POST'])]
    public function sign(Request $request, EntityManagerInterface $entityManager): Response
    {
        $presence = new Presence();
        $form = $this->createForm(PresenceSignatureType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère l'utilisateur actuellement connecté
            $user = $this->getUser();

            // On attribue l'utilisateur et l'heure actuelle à l'entité
            $presence->setStudent($user);
            $presence->setSignedAt(new \DateTime());

            // Sauvegarde dans la base de données (PostgreSQL via Doctrine)
            $entityManager->persist($presence);
            $entityManager->flush();

            // Message de succès qui s'affichera grâce à ton design existant
            $this->addFlash('success', 'Votre présence a été enregistrée avec succès.');

            // Redirection vers le tableau de bord
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        // Fait appel au fichier Twig que nous avons créé précédemment
        return $this->render('attendance/signature.html.twig', [
            'form' => $form,
        ]);
    }
}
