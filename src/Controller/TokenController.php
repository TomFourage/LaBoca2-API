<?php

namespace App\Controller;

use App\Repository\RefreshTokenRepository;

use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/token', name: 'api_token_')]
class TokenController extends AbstractController
{
    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refreshToken(
        Request $request,
        RefreshTokenRepository $repo,
        RefreshTokenService $refreshService,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $refreshTokenString = $data['refresh_token'] ?? null;

        if (!$refreshTokenString) {
            return $this->json(['error' => 'Token requis'], 400);
        }

        $refreshToken = $repo->findOneBy(['token' => $refreshTokenString]);

        if (!$refreshToken || $refreshToken->isExpired()) {
            return $this->json(['error' => 'Token invalide ou expiré'], 401);
        }

        $user = $refreshToken->getUser();
        $newJwt = $jwtManager->create($user);
        $newRefresh = $refreshService->createRefreshToken($user);

        return $this->json([
            'token' => $newJwt,
            'refresh_token' => $newRefresh->getToken(),
        ]);
    }
}
