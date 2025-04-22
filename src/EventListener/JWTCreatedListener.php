<?php

namespace App\EventListener;

use App\Service\RefreshTokenService; 
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
    public function __construct(
        private RefreshTokenService $refreshTokenService 
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $data = $event->getData();
        $data['refresh_token'] = $refreshToken->getToken();
        $event->setData($data);
    }
}
