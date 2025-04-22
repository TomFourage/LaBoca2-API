<?php

namespace App\Controller;

use App\Repository\IconRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/icons', name: 'api_icon_')]
final class IconController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(IconRepository $iconRepository): JsonResponse
    {
        return $this->json($iconRepository->findAll(), 200, [], ['groups' => 'icon:read']);
    }
}
