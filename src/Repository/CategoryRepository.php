<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxDisplayOrder(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('MAX(c.displayOrder)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function shiftDisplayOrdersFrom(int $order): void
    {
        $categories = $this->createQueryBuilder('c')
            ->where('c.displayOrder >= :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getResult();

        foreach ($categories as $cat) {
            $cat->setDisplayOrder($cat->getDisplayOrder() + 1);
        }
    }

    public function shiftDisplayOrdersBetween(int $from, int $to, bool $movingDown): void
    {
        $qb = $this->createQueryBuilder('c');

        if ($movingDown) {
            $qb->where('c.displayOrder <= :to')
               ->andWhere('c.displayOrder > :from');
        } else {
            $qb->where('c.displayOrder >= :to')
               ->andWhere('c.displayOrder < :from');
        }

        $categories = $qb
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        foreach ($categories as $cat) {
            $cat->setDisplayOrder(
                $cat->getDisplayOrder() + ($movingDown ? -1 : 1)
            );
        }
    }

    public function shiftAfterDelete(int $removedOrder): void
    {
        $categories = $this->createQueryBuilder('c')
            ->where('c.displayOrder > :order')
            ->setParameter('order', $removedOrder)
            ->getQuery()
            ->getResult();

        foreach ($categories as $cat) {
            $cat->setDisplayOrder($cat->getDisplayOrder() - 1);
        }
    }
}
