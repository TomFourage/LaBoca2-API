<?php

namespace App\Controller;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(
        Request $request,
        RefreshTokenRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['refresh_token'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token manquant'], 400);
        }

        $refresh = $repo->findOneBy(['token' => $token]);

        if ($refresh) {
            $em->remove($refresh);
            $em->flush();
        }

        return $this->json(['message' => 'Déconnexion réussie']);
    }
}
