<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthenticationFailureSubscriber implements EventSubscriberInterface
{
    public function onSecurityAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $data = [
            'status' => '401 Unauthorized',
            'message' => $event->getException()->getMessage(),
        ];

        $response = new JWTAuthenticationFailureResponse($data);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
           'lexik_jwt_authentication.on_authentication_failure' => 'onSecurityAuthenticationFailure',
        ];
    }
}
