<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubCategory::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.category', 'ASC')
            ->addOrderBy('s.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextDisplayOrder(Category $category): int
    {
        $max = $this->createQueryBuilder('s')
            ->select('MAX(s.displayOrder)')
            ->where('s.category = :cat')
            ->setParameter('cat', $category)
            ->getQuery()
            ->getSingleScalarResult();

        return $max ? $max + 1 : 1;
    }

    public function shiftDisplayOrdersFrom(Category $category, int $startingAt): void
    {
        $toShift = $this->createQueryBuilder('s')
            ->where('s.category = :cat')
            ->andWhere('s.displayOrder >= :order')
            ->setParameter('cat', $category)
            ->setParameter('order', $startingAt)
            ->getQuery()
            ->getResult();

        foreach ($toShift as $s) {
            $s->setDisplayOrder($s->getDisplayOrder() + 1);
        }
    }

    public function shiftDisplayOrdersBetween(Category $category, int $from, int $to, bool $movingDown): void
    {
        $qb = $this->createQueryBuilder('s')->where('s.category = :cat')->setParameter('cat', $category);

        if ($movingDown) {
            $qb->andWhere('s.displayOrder <= :to')
               ->andWhere('s.displayOrder > :from')
               ->setParameter('from', $from)
               ->setParameter('to', $to);
        } else {
            $qb->andWhere('s.displayOrder >= :to')
               ->andWhere('s.displayOrder < :from')
               ->setParameter('from', $from)
               ->setParameter('to', $to);
        }

        $toShift = $qb->getQuery()->getResult();

        foreach ($toShift as $s) {
            $s->setDisplayOrder($s->getDisplayOrder() + ($movingDown ? -1 : 1));
        }
    }

    public function shiftAfterDelete(Category $category, int $deletedOrder): void
    {
        $toShift = $this->createQueryBuilder('s')
            ->where('s.category = :cat')
            ->andWhere('s.displayOrder > :order')
            ->setParameter('cat', $category)
            ->setParameter('order', $deletedOrder)
            ->getQuery()
            ->getResult();

        foreach ($toShift as $s) {
            $s->setDisplayOrder($s->getDisplayOrder() - 1);
        }
    }
}

