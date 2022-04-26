<?php

namespace App\Repository;

use App\Constants;
use App\Entity\TRecipe;
use App\Entity\TRecipeIngredient;
use App\Entity\TRecipeTag;
use App\Entity\TTag;
use App\Entity\TUsers;
use App\Exceptions\FieldValidateException;
use App\Repository\General\AccessProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method TRecipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method TRecipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method TRecipe[]    findAll()
 * @method TRecipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeRepository extends AccessProvider
{

    protected static ?EntityManagerInterface  $obEntityManager = null;
    private static TypeRepository             $obTypeRepository;
    private static RecipeIngredientRepository $obRecipeIngredientRepository;
    private static IngredientRepository       $obIngredientRepository;
    private static TagRepository              $obTagRepository;
    private static RecipeTagRepository        $obRecipeTagRepository;

    /**
     * Recipe constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TRecipe::class);
        static::$obEntityManager              = $obEntityManager;
        static::$obTypeRepository             = new TypeRepository($obRegistry, $obEntityManager);
        static::$obUserRepository             = new UserRepository($obRegistry);
        static::$obRecipeIngredientRepository = new RecipeIngredientRepository($obRegistry, $obEntityManager, $this);
        static::$obIngredientRepository       = new IngredientRepository($obRegistry, $obEntityManager);
        static::$obTagRepository              = new TagRepository($obRegistry, $obEntityManager);
        static::$obRecipeTagRepository        = new RecipeTagRepository($obRegistry, $obEntityManager);
    }

    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TRecipe id
     * @throws FieldValidateException
     * @throws \Exception
     */
    public function put($arFields, TUsers $obUser): ?TRecipe
    {

        $obNewRecipe = $this->getEmpty($obUser);

        if (array_key_exists('id', $arFields) && $arFields['id']) {
            $obNewRecipe = $this->find($arFields['id']);
            if (!$obNewRecipe || !$obNewRecipe->checkCanEdit($obUser)) {
                throw new Exception('Рецепт не найден или у вас нет к нему доступа');
            }
        }

        if (array_key_exists('type', $arFields) && $arFields['type']) {
            if (is_array($arFields['type'])) {
                $arFields['type'] = $arFields['type']['id'] ?: $arFields['type']['name'];
            }

            $obType = static::$obTypeRepository->find($arFields['type']);

            if (!$obType) {
                throw new FieldValidateException('Неизвестный тип');
            }
        } else {
            $obType = static::$obTypeRepository->find(Constants::DEFAULT_RECIPE_TYPE);
        }

        $obNewRecipe
            ->setName($arFields['name'])
            ->setAnounce($arFields['anounce'])
            ->setXmlid(array_key_exists('xmlid', $arFields) ? $arFields['xmlid'] : '')
            ->setAccess(array_key_exists('access', $arFields) ? $arFields['access'] : $obNewRecipe->getAccess())
            ->setTotalTime($arFields['totalTime'])
            ->setStages($arFields['stages'])
            ->setDays($arFields['days'])
            ->setKkal($arFields['kkal'])
            ->setServing($arFields['serving'])
            ->setActiveTime($arFields['activeTime'])
            ->setType($obType)
            ->setDifficult($arFields['difficult']);

        static::$obEntityManager->persist($obNewRecipe);

        if (count($arFields['ingredients'])) {
            $arIngredients = [];

            foreach ($obNewRecipe->getIngredients() as $obRecipeIngredient) {
                $arIngredients[$obRecipeIngredient->getId()] = $obRecipeIngredient;
            }

            $arUniqueIngredients = [];

            foreach ($arFields['ingredients'] as $arIngredient) {
                if (array_key_exists($arIngredient['ingredient']['name'], $arUniqueIngredients)) {
                    $arUniqueIngredients[$arIngredient['ingredient']['name']]['amount'] += $arIngredient['amount'];
                } else {
                    $arUniqueIngredients[$arIngredient['ingredient']['name']] = $arIngredient;
                }
            }

            $arFields['ingredients'] = $arUniqueIngredients;

            $intSort = 0;

            foreach ($arFields['ingredients'] as $arIngredient) {
                if ($arIngredient['ingredient']) {
                    $intSort++;

                    $arIngredient['amount'] = floatval($arIngredient['amount']);

                    if (!$arIngredient['amount'] && !$arIngredient['taste']) {
                        throw new FieldValidateException(
                            'Неверное количество - ' . $arIngredient['ingredient']['name']
                        );
                    }

                    $obIngredient = static::$obIngredientRepository->find($arIngredient['ingredient']['id']);

                    $obExistRecipeIngredient = static::$obRecipeIngredientRepository
                        ->checkByIngredientAndRecipe($obNewRecipe, $obIngredient);

                    if (!$obExistRecipeIngredient) {
                        $obNewRecipeIngredient = new TRecipeIngredient();

                        $obNewRecipeIngredient
                            ->setRecipe($obNewRecipe)
                            ->setSort($intSort)
                            ->setIngredient($obIngredient)
                            ->setTaste(!!$arIngredient['taste'])
                            ->setAmount($arIngredient['amount']);

                        static::$obEntityManager->persist($obNewRecipeIngredient);
                    } else {
                        unset($arIngredients[$obExistRecipeIngredient->getId()]);
                        $obExistRecipeIngredient
                            ->setAmount($arIngredient['amount'])
                            ->setTaste($arIngredient['taste'])
                            ->setSort($intSort);
                        static::$obEntityManager->persist($obExistRecipeIngredient);
                    }
                }
            }

            foreach ($arIngredients as $obIngredientToDelete) {
                static::$obEntityManager->remove($obIngredientToDelete);
            }
        }


        if (count($arFields['tags'])) {

            $arTags = [];

            foreach ($obNewRecipe->getTags() as $obRecipeTag) {
                $arTags[$obRecipeTag->getId()] = $obRecipeTag;
            }

            $arUniqueTags = [];

            foreach ($arFields['tags'] as $arTag) {
                $arUniqueTags[$arTag['tag']['name']] = $arTag;
            }

            $arFields['tags'] = $arUniqueTags;

            $intSort = 0;

            foreach ($arFields['tags'] as $arTag) {
                if ($arTag['tag']) {
                    $intSort++;

                    $obTag = static::$obTagRepository->find($arTag['tag']['id']);

                    $obExistRecipeTag = static::$obRecipeTagRepository
                        ->checkByTagAndRecipe($obNewRecipe, $obTag);

                    if (!$obExistRecipeTag) {
                        $obNewRecipeTag = new TRecipeTag();

                        $obNewRecipeTag
                            ->setRecipe($obNewRecipe)
                            ->setSort($intSort)
                            ->setTag($obTag);

                        static::$obEntityManager->persist($obNewRecipeTag);
                    } else {
                        unset($arTags[$obExistRecipeTag->getId()]);
                        $obExistRecipeTag
                            ->setSort($intSort);
                        static::$obEntityManager->persist($obExistRecipeTag);
                    }
                }
            }

            foreach ($arTags as $obTagToDelete) {
                static::$obEntityManager->remove($obTagToDelete);
            }
        }

        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewRecipe);

        return $obNewRecipe;
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws \Exception
     */
    public function delete(int $intId, TUsers $obUser): bool
    {
        if ($obDeleteRecipe = $this->find($intId)) {
            $obDeleteRecipe->checkCanDelete($obUser);
            static::$obEntityManager->remove($obDeleteRecipe);
            static::$obEntityManager->flush();
            return true;
        }
        return false;
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return TRecipe|null
     */
    public function getEmpty(TUsers $obUser): ?TRecipe
    {
        $obRecipe = new TRecipe();

        $obRecipe
            // ->setName('Новый рецепт')
            ->setPics('')
            ->setXmlid('')
            // ->setAnounce('Кратко о рецепте')
            // ->setActiveTime(10)
            // ->setTotalTime(10)
            // ->setDescription('')
            ->setAuthor($obUser)
            // ->setDays(1)
            ->setType(static::$obTypeRepository->find(Constants::DEFAULT_RECIPE_TYPE))
            // ->setKkal(0)
            // ->setDifficult(3)
            ->setAccess('P')
            ->setIngredients(new PersistentCollection(
                static::$obEntityManager,
                new ClassMetadata(TRecipeIngredient::class),
                new ArrayCollection([])
            ))->setTags(new PersistentCollection(
                static::$obEntityManager,
                new ClassMetadata(TTag::class),
                new ArrayCollection([])
            ))->setId(0);

        return $obRecipe;
    }

    /**
     * @param QueryBuilder $obBuilder
     * @param TUsers       $obUser
     * @param array        $arParams
     * @return QueryBuilder
     */
    protected function buildFilterLocal(QueryBuilder $obBuilder, TUsers $obUser, array $arParams = []): QueryBuilder
    {
        if (array_key_exists('type', $arParams)) {
            if (!is_array($arParams['type'])) {
                $arParams['type'] = [$arParams['type']];
            }

            $arParams['type'] = array_filter($arParams['type']);

            foreach (self::$obTypeRepository->findBy(['parent' => $arParams['type']]) as $obChildType) {
                $arParams['type'][] = $obChildType->getId();
            }

            $arParams['type'] = array_unique($arParams['type']);

            if (count($arParams['type'])) {
                $obBuilder->andWhere('t.type IN (:types)')
                    ->setParameter('types', $arParams['type']);
            }
        }

        if (array_key_exists('name', $arParams)) {
            $obBuilder->andWhere('LOWER(t.name) LIKE LOWER(:name)')
                ->setParameter('name', '%' . $arParams['name'] . '%');
        }

        return $obBuilder;
    }
}
