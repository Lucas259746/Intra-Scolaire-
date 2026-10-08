<?php

namespace App\Service;

use App\Entity\UserActions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ActionLogger
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function log(UserInterface $user, string $action): void
    {
        $entry = new UserActions();
        $entry->setUserId($user);
        $entry->setAction($action);
        $entry->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($entry);
        $this->em->flush();
    }
}
