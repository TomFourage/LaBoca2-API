<?php

namespace App\Manager;

use App\Entity\Category;
use App\Entity\SubCategory;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubCategoryManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SubCategoryRepository $repo,
        private ValidatorInterface $validator
    ) {}

    public function createSubCategory(array $data, Category $category): SubCategory
    {
        $subCategory = new SubCategory();
        $subCategory->setName($data['name'] ?? null);
        $subCategory->setCreatedAt(new \DateTimeImmutable());
        $subCategory->setCategory($category);

        $order = $data['displayOrder'] ?? $this->repo->getNextDisplayOrder($category);
        $subCategory->setDisplayOrder($order);
        $this->repo->shiftDisplayOrdersFrom($category, $order);

        $this->validate($subCategory);

        $this->em->persist($subCategory);
        $this->em->flush();

        return $subCategory;
    }

    public function updateSubCategory(SubCategory $subCategory, array $data): SubCategory
    {
        if (isset($data['name'])) {
            $subCategory->setName($data['name']);
        }

        if (isset($data['displayOrder'])) {
            $newOrder = (int)$data['displayOrder'];
            $currentOrder = $subCategory->getDisplayOrder();
            $category = $subCategory->getCategory();

            if ($newOrder !== $currentOrder) {
                $movingDown = $newOrder > $currentOrder;
                $this->repo->shiftDisplayOrdersBetween($category, $currentOrder, $newOrder, $movingDown);
                $subCategory->setDisplayOrder($newOrder);
            }
        }

        $subCategory->setUpdatedAt(new \DateTime());
        $this->validate($subCategory);

        $this->em->flush();

        return $subCategory;
    }

    public function deleteSubCategory(SubCategory $subCategory): void
    {
        if ($subCategory->getDishes()->count() > 0) {
            throw new BadRequestHttpException('Des plats sont encore associés à cette sous-catégorie.');
        }

        $category = $subCategory->getCategory();
        $order = $subCategory->getDisplayOrder();

        $this->em->remove($subCategory);
        $this->repo->shiftAfterDelete($category, $order);
        $this->em->flush();
    }

    private function validate(SubCategory $subCategory): void
    {
        $errors = $this->validator->validate($subCategory);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new BadRequestHttpException(json_encode($messages));
        }
    }
}
