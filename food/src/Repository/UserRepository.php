<?php

namespace App\Repository;

use App\Entity\TUsers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TUsers|null find($id, $lockMode = null, $lockVersion = null)
 * @method TUsers|null findOneBy(array $criteria, array $orderBy = null)
 * @method TUsers[]    findAll()
 * @method TUsers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * Users constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TUsers::class);
    }

    /**
     * @param string $role
     * @return TUsers|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByRole(string $role): ?TUsers
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.roles LIKE :val')
            ->setParameter('val', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
