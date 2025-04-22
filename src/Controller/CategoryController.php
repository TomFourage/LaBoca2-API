<?php

namespace App\Controller;

use App\Entity\Category;
use App\Manager\CategoryManager;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories', name: 'api_category_')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAllOrdered();
        return $this->json($categories, 200, [], ['groups' => 'category:read']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CategoryRepository $categoryRepository, int $id): JsonResponse
    {
        $category = $categoryRepository->find($id);

        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], 404);
        }

        return $this->json($category, 200, [], ['groups' => 'category:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, CategoryManager $manager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $category = $manager->createCategory($data);
            return $this->json($category, 201, [], ['groups' => 'category:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(Request $request, CategoryRepository $repo, CategoryManager $manager, int $id): JsonResponse
    {
        $category = $repo->find($id);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $updated = $manager->updateCategory($category, $data);
            return $this->json($updated, 200, [], ['groups' => 'category:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CategoryRepository $repo, CategoryManager $manager, int $id): JsonResponse
    {
        $category = $repo->find($id);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], 404);
        }

        try {
            $manager->deleteCategory($category);
            return $this->json(['message' => 'Catégorie supprimée avec succès']);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
