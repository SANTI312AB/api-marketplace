<?php

namespace App\EventSubscriber;

use App\Entity\Login;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use DateTime;

class LoginListener implements EventSubscriberInterface
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager,  RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof Login) {
            $user->setLastLogin(new DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }
}