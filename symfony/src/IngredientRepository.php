<?php

namespace App\Repository;

use App\Constants;
use App\Entity\TIngredient;
use App\Entity\TUsers;
use App\Exceptions\ExceptionFactory;
use App\Exceptions\FieldValidateException;
use App\Repository\General\AccessProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method TIngredient|null find($id, $lockMode = null, $lockVersion = null)
 * @method TIngredient|null findOneBy(array $criteria, array $orderBy = null)
 * @method TIngredient[]    findAll()
 * @method TIngredient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IngredientRepository extends AccessProvider
{

    protected static ?EntityManagerInterface $obEntityManager = null;
    private static UnitRepository            $obUnitRepository;
    private static IngredientTypeRepository  $obIngredientTypeRepository;

    /**
     * Ingredient constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TIngredient::class);
        static::$obEntityManager            = $obEntityManager;
        static::$obUnitRepository           = new UnitRepository($obRegistry);
        static::$obUserRepository           = new UserRepository($obRegistry);
        static::$obIngredientTypeRepository = new IngredientTypeRepository($obRegistry);
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
        $obUser = static::$obUserRepository->find($obUser->getId());

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
        static::$obEntityManager->refresh($obNewIngredient);

        return $obNewIngredient;
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
}
