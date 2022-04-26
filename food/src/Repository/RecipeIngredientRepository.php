<?php

namespace App\Repository;

use App\Entity\TIngredient;
use App\Entity\TRecipe;
use App\Entity\TRecipeIngredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TRecipeIngredient|null find($id, $lockMode = null, $lockVersion = null)
 * @method TRecipeIngredient|null findOneBy(array $criteria, array $orderBy = null)
 * @method TRecipeIngredient[]    findAll()
 * @method TRecipeIngredient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeIngredientRepository extends ServiceEntityRepository
{
    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Ingredient constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(//@formatter:off
        ManagerRegistry $obRegistry,
        EntityManagerInterface $obEntityManager
    ) {
        parent::__construct($obRegistry, TRecipeIngredient::class);
        static::$obEntityManager        = $obEntityManager;
    }
    //@formatter:on

    /**
     * @param array $arFields
     *
     * @return TRecipeIngredient id
     * @throws \Exception
     */
    public function add($arFields): ?TRecipeIngredient
    {

        $obNewIngredient = new TRecipeIngredient();

        $obNewIngredient
            ->setRecipe($arFields['recipe'])
            ->setIngredient($arFields['ingredient'])
            ->setAmount($arFields['amount']);

        static::$obEntityManager->persist($obNewIngredient);
        static::$obEntityManager->flush();

        return $obNewIngredient;
    }

    /**
     * @param TRecipe     $obRecipe
     * @param TIngredient $obIngredient
     * @return TRecipeIngredient|null Returns an array of TType objects
     * @throws NonUniqueResultException
     */
    public function checkByIngredientAndRecipe(TRecipe $obRecipe, TIngredient $obIngredient): ?TRecipeIngredient
    {

        if (!$obIngredient->getId() || !$obRecipe->getId()) {
            return null;
        }

        return $this->createQueryBuilder('t')
            ->andWhere('t.recipe = :valRecipe AND t.ingredient = :valIng')
            ->setParameter('valIng', $obIngredient->getId())
            ->setParameter('valRecipe', $obRecipe->getId() ?: 0)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
