<?php

namespace App\EventListener;

use App\Service\ActionLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
#[AsEventListener(event: LogoutEvent::class)]
class AuthenticationListener
{
    public function __construct(private ActionLogger $logger) {}

    public function __invoke(LoginSuccessEvent|LogoutEvent $event): void
    {
        $user = match (true) {
            $event instanceof LoginSuccessEvent => $event->getUser(),
            $event instanceof LogoutEvent => $event->getToken()?->getUser(),
            default => null,
        };

        if ($user === null) return;

        $action = $event instanceof LoginSuccessEvent ? 'LOGIN' : 'LOGOUT';
        $this->logger->log($user, $action);
    }
}
