<?php

namespace App\Manager;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepo,
        private ValidatorInterface $validator
    ) {}

    public function createCategory(array $data): Category
    {
        $category = new Category();
        $category->setName($data['name'] ?? null);
        $category->setIcon($data['icon'] ?? null);
        $category->setCreatedAt(new \DateTimeImmutable());

        $order = $data['displayOrder'] ?? $this->categoryRepo->getMaxDisplayOrder() + 1;
        $category->setDisplayOrder($order);
        $this->categoryRepo->shiftDisplayOrdersFrom($order);

        $this->validate($category);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    public function updateCategory(Category $category, array $data): Category
    {
        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        if (isset($data['icon'])) {
            $category->setIcon($data['icon']);
        }

        if (isset($data['displayOrder'])) {
            $newOrder = (int) $data['displayOrder'];
            $currentOrder = $category->getDisplayOrder();

            if ($newOrder !== $currentOrder) {
                $movingDown = $newOrder > $currentOrder;
                $this->categoryRepo->shiftDisplayOrdersBetween($currentOrder, $newOrder, $movingDown);
                $category->setDisplayOrder($newOrder);
            }
        }

        $category->setUpdatedAt(new \DateTime());

        $this->validate($category);
        $this->em->flush();

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        if (count($category->getSubCategories()) > 0 || count($category->getDishes()) > 0) {
            throw new BadRequestHttpException("Impossible de supprimer : sous-catégories ou plats présents.");
        }

        $order = $category->getDisplayOrder();

        $this->em->remove($category);
        $this->categoryRepo->shiftAfterDelete($order);
        $this->em->flush();
    }

    private function validate(Category $category): void
    {
        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new BadRequestHttpException(json_encode($messages));
        }
    }
}
