<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param array{
     *     category?: Category|null,
     * } $data
     */
    public function findAllCategoryByQueryBuilderAndFilter(array $data = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('category')
            ->select('category', 'recipe')
            ->leftJoin('category.recipes', 'recipe')
            ->orderBy('category.name', 'ASC')
        ;

        if (isset($data['category'])) {
            $qb->andWhere('category = :category')
                ->setParameter('category', $data['category']);
        }

        return $qb;
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
