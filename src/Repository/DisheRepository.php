<?php

namespace App\Repository;

use App\Entity\Dishe;
use App\Entity\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DisheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dishe::class);
    }

    public function getNextDisplayOrderForSubCategory(SubCategory $subCategory): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('MAX(d.displayOrder)')
            ->where('d.subCategory = :subCat')
            ->setParameter('subCat', $subCategory);

        $max = $qb->getQuery()->getSingleScalarResult();
        return $max ? $max + 1 : 1;
    }

    public function reorderDishes(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $dish = $this->find($id);
            if ($dish) {
                $dish->setDisplayOrder($index + 1);
            }
        }
    }
}
