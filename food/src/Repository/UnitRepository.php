<?php

namespace App\Repository;

use App\Entity\TUnits;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TUnits|null find($id, $lockMode = null, $lockVersion = null)
 * @method TUnits|null findOneBy(array $criteria, array $orderBy = null)
 * @method TUnits[]    findAll()
 * @method TUnits[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitRepository extends ServiceEntityRepository
{
    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Units constructor.
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TUnits::class);
        static::$obEntityManager = $obEntityManager;
    }


    /**
     * @param array $arFields
     *
     * @return TUnits
     * @throws \App\Exceptions\FieldValidateException
     */
    public function put($arFields): TUnits
    {

        $obNewUnit = new TUnits();

        $obNewUnit
            ->setName($arFields['name'])
            ->setShort(array_key_exists('short', $arFields) ? $arFields['short'] : $arFields['name'])
            ->setStep(array_key_exists('step', $arFields) ? $arFields['step'] : 1);

        static::$obEntityManager->persist($obNewUnit);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewUnit);

        return $obNewUnit;
    }

    /**
     * @param string $name
     * @return TUnits|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName(string $name): ?TUnits
    {

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) LIKE :val')
            ->setParameter('val', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $shortName
     * @return TUnits|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByShortName(string $shortName): ?TUnits
    {

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.short) LIKE :val')
            ->setParameter('val', mb_strtolower($shortName))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
