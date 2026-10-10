<?php

declare(strict_types=1);

namespace App\Repository\Recipe;

use App\Entity\Recipe\Category;
use App\Entity\Recipe\Recipe;
use App\Entity\Recipe\Tag;
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
            ->getSingleScalarResult();
    }

    /**
     * Returns all recipes required for managing
     * featured recipes.
     *
     * Promoted recipes are placed first,
     * then sorted by position and finally by title.
     *
     * @return list<Recipe>
     */
    public function findAllForPromotion(): array
    {
        return $this->createQueryBuilder('recipe')
            ->orderBy('recipe.promoted', 'DESC')
            ->addOrderBy('recipe.position', 'ASC')
            ->addOrderBy('recipe.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Recipe>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('recipe')
            ->andWhere('recipe.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Remove promotion from every recipe.
     */
    public function removePromotionFromAll(): void
    {
        $this->createQueryBuilder('recipe')
            ->update()
            ->set('recipe.promoted', ':promoted')
            ->set('recipe.online', ':online')
            ->set('recipe.position', ':position')
            ->setParameter('promoted', false)
            ->setParameter('online', false)
            ->setParameter('position', null)
            ->getQuery()
            ->execute();
    }

    /**
     * @param list<int> $ids
     */
    public function removePromotionFromOtherRecipes(array $ids): void
    {
        if ([] === $ids) {
            $this->removePromotionFromAll();

            return;
        }

        $this->createQueryBuilder('recipe')
            ->update()
            ->set('recipe.promoted', ':promoted')
            ->set('recipe.position', ':position')
            ->where('recipe.id NOT IN (:ids)')
            ->setParameter('ids', $ids)
            ->setParameter('promoted', false)
            ->setParameter('position', null)
            ->getQuery()
            ->execute();
    }

    /**
     * @return list<Recipe>
     */
    public function findOnlineForPublicApi(): array
    {
        return $this->createQueryBuilder('recipe')
            ->andWhere('recipe.promoted = :promoted')
            ->andWhere('recipe.online = :online')
            ->setParameter('online', true)
            ->setParameter('promoted', true)
            ->orderBy('recipe.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneOnlineForPublicApi(int $id): ?Recipe
    {
        return $this->createQueryBuilder('recipe')
            ->andWhere('recipe.id = :id')
            ->andWhere('recipe.online = :online')
            ->andWhere('recipe.promoted = :promoted')
            ->setParameter('id', $id)
            ->setParameter('online', true)
            ->setParameter('promoted', true)
            ->getQuery()
            ->getOneOrNullResult();
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
