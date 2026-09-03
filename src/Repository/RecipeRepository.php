<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Recipe;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * @param array{
     *     title?: string,
     *     category?: Category|null,
     *     tags?: list<Tag>
     * } $data
     */
    public function findWithDurationLowerThan(int $duration, array $data = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('recipe')
            ->select('recipe', 'category', 'tag')
            ->leftJoin('recipe.category', 'category')
            ->leftJoin('recipe.tags', 'tag')
            ->where('recipe.duration < :val')
            ->setParameter('val', $duration)
            ->orderBy('recipe.duration', 'ASC');

        if (isset($data['title']) && '' !== $data['title']) {
            $qb->andWhere('LOWER(recipe.title) LIKE LOWER(:title)')
                ->setParameter('title', '%'.$data['title'].'%');
        }

        if (isset($data['category'])) {
            $qb->andWhere('category = :category')
                ->setParameter('category', $data['category']);
        }

        if (isset($data['tags']) && [] !== $data['tags']) {
            $qb->andWhere('tag IN (:tags)')
                ->setParameter('tags', $data['tags']);
        }

        return $qb;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findTotalDuration(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('SUM(r.duration) as total')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function save(Recipe $entity, bool $flush = false): void
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

    public function remove(Recipe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
