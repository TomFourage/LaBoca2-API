<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Manager\SubCategoryManager;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/subcategories', name: 'api_subcategory_')]
class SubCategoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SubCategoryRepository $repo): JsonResponse
    {
        return $this->json($repo->findAllOrdered(), 200, [], ['groups' => 'subcategory:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        CategoryRepository $categoryRepo,
        SubCategoryManager $manager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['category_id'])) {
            return $this->json(['error' => 'La catégorie est obligatoire.'], 400);
        }

        $category = $categoryRepo->find($data['category_id']);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable.'], 404);
        }

        try {
            $subCategory = $manager->createSubCategory($data, $category);
            return $this->json($subCategory, 201, [], ['groups' => 'subcategory:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(
        Request $request,
        SubCategory $subCategory,
        SubCategoryManager $manager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $updated = $manager->updateSubCategory($subCategory, $data);
            return $this->json($updated, 200, [], ['groups' => 'subcategory:read']);
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()]
            ], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(SubCategory $subCategory, SubCategoryManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $manager->deleteSubCategory($subCategory);
            return $this->json(['message' => 'Sous-catégorie supprimée avec succès.']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
