<?php

namespace App\Repository;

use App\Constants;
use App\Entity\TIngredient;
use App\Entity\TUsers;
use App\Exceptions\ExceptionFactory;
use App\Exceptions\FieldValidateException;
use App\Repository\Interfaces\Accessible;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method TIngredient|null find($id, $lockMode = null, $lockVersion = null)
 * @method TIngredient|null findOneBy(array $criteria, array $orderBy = null)
 * @method TIngredient[]    findAll()
 * @method TIngredient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IngredientRepository extends ServiceEntityRepository implements Accessible
{
    private static ?EntityManagerInterface  $obEntityManager = null;
    private static UnitRepository           $obUnitRepository;
    private static IngredientTypeRepository $obIngredientTypeRepository;

    /**
     * Ingredient constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TIngredient::class);
        if (!static::$obEntityManager) {
            static::$obEntityManager            = $obEntityManager;
            static::$obUnitRepository           = new UnitRepository($obRegistry);
            static::$obIngredientTypeRepository = new IngredientTypeRepository($obRegistry);
        }
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return TIngredient[] Returns an array of TType objects
     */
    public function getVisibleForUser(TUsers $obUser): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.author = :user OR t.access IN (:access)')
            ->setParameter('user', $obUser->getId())
            ->setParameter('access', $obUser->getAccessViews())
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TIngredient ingredient
     * @throws FieldValidateException
     * @throws \Exception
     */
    public function put(array $arFields, TUsers $obUser): ?TIngredient
    {
        if (array_key_exists('id', $arFields) && $arFields['id']) {
            $obNewIngredient = $this->find($arFields['id']);
            if (!$obNewIngredient) {
                ExceptionFactory::getException(ExceptionFactory::NOT_FOUND, false, 'Ингредиент');
            }
            $obNewIngredient->checkCanEdit($obUser);
            try {
                if ($this->checkByName($arFields['name'])->getId() != $obNewIngredient->getId()) {
                    ExceptionFactory::getException(ExceptionFactory::INGREDIENT_NAME_EXISTS);
                }
            } catch (NonUniqueResultException $obException) {
                ExceptionFactory::pushException($obException);
            }
        } else {
            try {
                if ($this->checkByName($arFields['name'])) {
                    ExceptionFactory::getException(ExceptionFactory::INGREDIENT_NAME_EXISTS);
                }
            } catch (NonUniqueResultException $obException) {
                ExceptionFactory::pushException($obException);
            }
            $obNewIngredient = $this->getEmpty($obUser);
        }

        if (array_key_exists('units', $arFields) && $arFields['units']) {
            $obUnit = static::$obUnitRepository->find($arFields['units']);
            if (!$obUnit) {
                ExceptionFactory::getException(ExceptionFactory::NOT_FOUND, false, 'Единица измерения');
            }
        } else {
            $obUnit = static::$obUnitRepository->find(Constants::DEFAULT_UNIT);
        }

        if (array_key_exists('type', $arFields) && ($arFields['type'] = intval($arFields['type']))) {
            $obType = static::$obIngredientTypeRepository->find($arFields['type']);
            if (!$obType) {
                ExceptionFactory::getException(ExceptionFactory::NOT_FOUND, false, 'Тип');
            }
        } else {
            $obType = static::$obIngredientTypeRepository->find(Constants::DEFAULT_TYPE);
        }

        if (!array_key_exists('minimum', $arFields) || !$arFields['minimum']) {
            $arFields['minimum'] = 1;
        }

        $obNewIngredient
            ->setName($arFields['name'])
            ->setUnits($obUnit)
            ->setType($obType)
            ->setMinimum($arFields['minimum']);

        static::$obEntityManager->persist($obNewIngredient);
        static::$obEntityManager->flush();

        return $obNewIngredient;
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws \Exception
     */
    public function delete(int $intId, TUsers $obUser): bool
    {
        if ($obDeleteIngredient = $this->find($intId)) {
            $obDeleteIngredient->checkCanDelete($obUser);
            if ($obDeleteIngredient->getCanDelete()) {
                static::$obEntityManager->remove($obDeleteIngredient);
                static::$obEntityManager->flush();
            } else {
                ExceptionFactory::getException(ExceptionFactory::NO_ACCESS_EDIT);
            }
        }

        return true;
    }

    /**
     * @param TUsers|UserInterface $obUser
     *
     * @return TIngredient|null
     * @throws \Exception
     */
    public function getEmpty(TUsers $obUser): ?TIngredient
    {
        $obIngredient = new TIngredient();

        try {
            $obIngredient
                ->setName('Название')
                ->setUnits(static::$obUnitRepository->find(Constants::DEFAULT_UNIT))
                ->setType(static::$obIngredientTypeRepository->find(Constants::DEFAULT_TYPE))
                ->setMinimum(1)
                ->setAccess('P')
                ->setAuthor($obUser)
                ->setId(0);
        } catch (FieldValidateException $eException) {
            return null;
        }

        return $obIngredient;
    }

    /**
     * @param string               $name
     * @param TUsers|UserInterface $obUser
     * @return TIngredient[] Returns an array of TType objects
     */
    public function findByName(string $name, TUsers $obUser): array
    {

        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :val AND (t.author = :user OR t.access = :access)')
            ->setParameter('val', $name)
            ->setParameter('user', $obUser->getId())
            ->setParameter('access', 'O')
            ->setMaxResults(10)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return TIngredient[] Returns an array of TType objects
     */
    public function findMyUnmoderated(TUsers $obUser): array
    {

        return $this->createQueryBuilder('t')
            ->andWhere('t.author = :user AND t.access = :access')
            ->setParameter('user', $obUser->getId())
            ->setParameter('access', 'P')
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * @param string $name
     * @return TIngredient|null Returns an array of TIngredient objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkByName(string $name): ?TIngredient
    {

        return $this->createQueryBuilder('t')
            ->andWhere('t.name = :val')
            ->setParameter('val', $name)
            ->setMaxResults(10)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string               $name
     * @param TUsers|UserInterface $obUser
     * @return TIngredient|null Returns an array of TType objects
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName(string $name, TUsers $obUser): ?TIngredient
    {

        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :val  AND (t.author = :user OR t.access = :access)')
            ->setParameter('val', $name)
            ->setParameter('access', 'O')
            ->setParameter('user', $obUser->getId())
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int                  $id
     * @param TUsers|UserInterface $obUser
     * @return object|void|null
     */
    public function getVisibleByID(int $id, TUsers $obUser)
    {
        try {
            return $this->createQueryBuilder('t')
                ->andWhere('t.id=:id AND (t.author = :user OR t.access IN (:access))')
                ->setParameter('user', $obUser->getId())
                ->setParameter('id', $id)
                ->setParameter('access', $obUser->getAccessViews())
                ->orderBy('t.name', 'ASC')
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $eException) {
            return null;
        }
    }
}
