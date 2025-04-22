<?php

namespace App\Controller;

use App\Entity\Dishe;
use App\Manager\DisheManager;
use App\Repository\DisheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dishes', name: 'api_dish_')]
class DisheController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(DisheRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll(), 200, [], ['groups' => 'dish:read']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Dishe $dish): JsonResponse
    {
        return $this->json($dish, 200, [], ['groups' => 'dish:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, DisheManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $dish = $manager->create($data);
            return $this->json($dish, 201, [], ['groups' => 'dish:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(Request $request, Dishe $dish, DisheManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $updated = $manager->update($dish, $data);
            return $this->json($updated, 200, [], ['groups' => 'dish:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Dishe $dish, DisheManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $manager->delete($dish);
            return $this->json(['message' => 'Plat supprimé avec succès']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(Request $request, DisheManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['orderedIds']) || !is_array($data['orderedIds'])) {
            return $this->json(['error' => 'Liste invalide'], 400);
        }

        try {
            $manager->reorder($data['orderedIds']);
            return $this->json(['message' => 'Ordre mis à jour']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
