<?php

namespace App\Manager;

use App\Entity\Dishe;
use App\Repository\CategoryRepository;
use App\Repository\DisheRepository;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DisheManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepo,
        private SubCategoryRepository $subCategoryRepo,
        private DisheRepository $disheRepo,
        private ValidatorInterface $validator
    ) {}

    public function create(array $data): Dishe
    {
        $dish = new Dishe();
        $dish->setName($data['name'] ?? null);
        $dish->setCreatedAt(new \DateTimeImmutable());

        // SubCategory & Category logic
        if (isset($data['sub_category_id'])) {
            $subCategory = $this->subCategoryRepo->find($data['sub_category_id']);
            if ($subCategory) {
                $dish->setSubCategory($subCategory);
                $dish->setCategory($subCategory->getCategory());

                if (isset($data['category_id'])) {
                    $category = $this->categoryRepo->find($data['category_id']);
                    if ($category && $subCategory->getCategory()->getId() !== $category->getId()) {
                        throw new BadRequestHttpException("La sous-catégorie ne correspond pas à la catégorie fournie.");
                    }
                }

                $dish->setDisplayOrder($this->disheRepo->getNextDisplayOrderForSubCategory($subCategory));
            }
        }

        // Fallback category
        if (!$dish->getCategory() && isset($data['category_id'])) {
            $category = $this->categoryRepo->find($data['category_id']);
            if ($category) {
                $dish->setCategory($category);
            }
        }

        $dish->setPrice($data['price'] ?? null);
        $dish->setDescription($data['description'] ?? null);

        $this->validate($dish);

        $this->em->persist($dish);
        $this->em->flush();

        return $dish;
    }

    public function update(Dishe $dish, array $data): Dishe
    {
        if (isset($data['name'])) {
            $dish->setName($data['name']);
        }

        if (isset($data['price'])) {
            $dish->setPrice($data['price']);
        }

        if (isset($data['description'])) {
            $dish->setDescription($data['description']);
        }

        if (isset($data['sub_category_id'])) {
            $subCategory = $this->subCategoryRepo->find($data['sub_category_id']);
            if ($subCategory) {
                $dish->setSubCategory($subCategory);
            }
        }

        if (isset($data['category_id'])) {
            $category = $this->categoryRepo->find($data['category_id']);
            if ($category) {
                $dish->setCategory($category);
            }
        }

        $dish->setUpdatedAt(new \DateTime());

        $this->validate($dish);
        $this->em->flush();

        return $dish;
    }

    public function delete(Dishe $dish): void
    {
        $this->em->remove($dish);
        $this->em->flush();
    }

    public function reorder(array $orderedIds): void
    {
        $this->disheRepo->reorderDishes($orderedIds);
        $this->em->flush();
    }

    private function validate(Dishe $dish): void
    {
        $errors = $this->validator->validate($dish);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new BadRequestHttpException(json_encode($messages));
        }
    }
}
