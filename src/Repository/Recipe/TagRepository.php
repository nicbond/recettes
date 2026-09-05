<?php

namespace App\Repository\Recipe;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @param array{
     *     tags?: list<Tag>
     * } $data
     */
    public function findAllTagsByQueryBuilderAndFilter(array $data = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('tag')
            ->select('tag', 'recipe')
            ->leftJoin('tag.recipes', 'recipe')
            ->orderBy('tag.name', 'ASC')
        ;

        if (isset($data['tags']) && [] !== $data['tags']) {
            $qb->andWhere('tag IN (:tags)')
                ->setParameter('tags', $data['tags']);
        }

        return $qb;
    }

    public function save(Tag $entity, bool $flush = false): void
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

    public function remove(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
