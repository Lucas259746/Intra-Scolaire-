<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
class TurboFrameGuardListener
{
    private const PUBLIC_ROUTES = ['app_dashboard', 'app_login', 'app_register', 'app_logout', 'app_profile', 'app_profile_edit', 'app_admin_users', 'app_admin_set_role', 'app_students_show', 'app_admin_members', 'app_admin_members_new', 'app_absences_index', 'app_absences_new', 'app_absences_show', 'app_absences_edit', 'app_absences_delete', 'app_projects_index', 'app_projects_new', 'app_projects_show', 'app_projects_edit', 'app_projects_delete', 'app_projects_toggle', 'app_projects_sendback', 'app_grades_index', 'app_grades_new', 'app_grades_show', 'app_grades_edit', 'app_grades_delete', 'app_attendance_sheet', 'app_attendance_sign', 'app_attendance'];

    public function __construct(private RouterInterface $router) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Security or another listener already set a response (e.g. redirect to login)
        if ($event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();

        // Allow Turbo Frame requests (navigation inside the frame)
        if ($request->headers->has('Turbo-Frame')) {
            return;
        }

        $route = $request->attributes->get('_route', '');

        // Allow public/system routes and asset requests
        if (
            in_array($route, self::PUBLIC_ROUTES, true) ||
            str_starts_with($route, '_') ||
            str_starts_with($request->getPathInfo(), '/assets/')
        ) {
            return;
        }

        // Route is known but accessed directly → redirect to dashboard
        if ($route !== '') {
            $event->setResponse(new RedirectResponse($this->router->generate('app_dashboard')));
        }
    }
}
