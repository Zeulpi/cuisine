<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTPayloadCustomizer implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_created' => 'onJWTCreated',
        ];
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var \App\Entity\User $user */
        $user = $event->getUser();

        $payload = $event->getData();
        $payload['email'] = $user->getEmail(); // 👈 ajoute l'email au token
        $payload['userImage'] = $user->getUserImg();
        $payload['planner'] = $user->getUserPlanners();

        $event->setData($payload);
    }
}
