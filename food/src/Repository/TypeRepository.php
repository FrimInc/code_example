<?php

namespace App\Repository;

use App\Entity\TType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TType|null find($id, $lockMode = null, $lockVersion = null)
 * @method TType|null findOneBy(array $criteria, array $orderBy = null)
 * @method TType[]    findAll()
 * @method TType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeRepository extends ServiceEntityRepository
{
    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Type constructor.
     *
     * @param ManagerRegistry                      $registry
     * @param \Doctrine\ORM\EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($registry, TType::class);
        static::$obEntityManager = $obEntityManager;
    }


    /**
     * @param array $arFields
     *
     * @return TType id
     * @throws \App\Exceptions\FieldValidateException
     */
    public function put($arFields): TType
    {
        $obNewType = new TType();

        $obNewType->setName($arFields['name']);

        static::$obEntityManager->persist($obNewType);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewType);

        $obNewType->setParent($obNewType);

        static::$obEntityManager->persist($obNewType);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewType);

        return $obNewType;
    }

    /**@param string $name
     * @return TType[] Returns an array of TType objects
     */
    public function findByName(string $name = ''): array
    {

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) LIKE :val')
            ->setParameter('val', mb_strtolower($name))
            ->orderBy('t.name', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $name
     * @return TType|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName(string $name): ?TType
    {
        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) LIKE :val')
            ->setParameter('val', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
