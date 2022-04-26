<?php

namespace App\Repository;

use App\Entity\TIngredientType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TIngredientType|null find($id, $lockMode = null, $lockVersion = null)
 * @method TIngredientType|null findOneBy(array $criteria, array $orderBy = null)
 * @method TIngredientType[]    findAll()
 * @method TIngredientType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IngredientTypeRepository extends ServiceEntityRepository
{

    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Units constructor.
     *
     * @param \Doctrine\Persistence\ManagerRegistry $obRegistry
     * @param \Doctrine\ORM\EntityManagerInterface  $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TIngredientType::class);
        static::$obEntityManager = $obEntityManager;
    }

    /**
     * @param array $arFields
     *
     * @return TIngredientType
     * @throws \App\Exceptions\FieldValidateException
     */
    public
    function put($arFields): TIngredientType
    {

        $obNewType = new TIngredientType();

        $obNewType->setName($arFields['name']);

        static::$obEntityManager->persist($obNewType);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewType);

        return $obNewType;
    }

    /**@param string $name
     * @return TIngredientType[] Returns an array of TIngredientType objects
     */
    public
    function findByName(string $name = ''): array
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
     * @return TIngredientType|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public
    function findOneByName(string $name): ?TIngredientType
    {

        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :val')
            ->setParameter('val', $name)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
