<?php

namespace App\Repository;

use App\Entity\TTag;
use App\Entity\TRecipe;
use App\Entity\TRecipeTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TRecipeTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TRecipeTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TRecipeTag[]    findAll()
 * @method TRecipeTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeTagRepository extends ServiceEntityRepository
{
    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Tag constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TRecipeTag::class);
        static::$obEntityManager = $obEntityManager;
    }

    /**
     * @param array $arFields
     *
     * @return TRecipeTag id
     */
    public function add($arFields): ?TRecipeTag
    {

        $obNewTag = new TRecipeTag();

        $obNewTag
            ->setRecipe($arFields['recipe'])
            ->setTag($arFields['tag']);

        static::$obEntityManager->persist($obNewTag);
        static::$obEntityManager->flush();

        return $obNewTag;
    }

    /**
     * @param TRecipe $obRecipe
     * @param TTag    $obTag
     * @return TRecipeTag|null Returns an array of TType objects
     * @throws NonUniqueResultException
     */
    public function checkByTagAndRecipe(TRecipe $obRecipe, TTag $obTag): ?TRecipeTag
    {

        if (!$obTag->getId() || !$obRecipe->getId()) {
            return null;
        }

        return $this->createQueryBuilder('t')
            ->andWhere('t.recipe = :valRecipe AND t.tag = :valIng')
            ->setParameter('valIng', $obTag->getId())
            ->setParameter('valRecipe', $obRecipe->getId() ?: 0)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
