<?php

namespace App\Repository\User;

use App\Entity\User\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Admin>
 */
class AdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    public function save(Admin $entity, bool $flush = false): void
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

    /**
     * @param array{
     *     email?: string,
     * } $data
     */
    public function findAllAdmins(array $data = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('admin');

        if (isset($data['email']) && '' !== trim((string) $data['email'])) {
            $qb->andWhere('admin.email LIKE :email')
                ->setParameter('email', '%'.$data['email'].'%');
        }

        return $qb;
    }
}
