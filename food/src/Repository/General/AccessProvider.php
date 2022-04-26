<?php

namespace App\Repository\General;

use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use App\Repository\IngredientRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AccessProvider extends ServiceEntityRepository
{
    protected static UserRepository $obUserRepository;

    public const ACCESS_STATUS
        = [
            'O' => 'Опубликован',
            'M' => 'Ожидает модерации',
            'P' => 'Закрытый'
        ];

    /**
     * AccessProvider constructor.
     *
     * @param \Doctrine\Persistence\ManagerRegistry $obRegistry
     * @param string                                $strClassName
     */
    public function __construct(ManagerRegistry $obRegistry, $strClassName = '\App\Entity\TUsers')
    {
        parent::__construct($obRegistry, $strClassName);
    }

    /**
     * @param int    $intId
     * @param TUsers $obUser
     * @return object
     * @throws \Exception
     */
    public function makePublish(int $intId, TUsers $obUser)
    {
        $obItem = null;

        if ($intId) {
            $obItem = $this->getVisibleByID($intId, $obUser);
            if (!$obItem) {
                ExceptionService::getException(ExceptionService::NOT_FOUND);
            }
        } else {
            ExceptionService::getException(ExceptionService::NOT_FOUND);
        }

        if ($obItem->getAccess() === 'O') {
            ExceptionService::getException(ExceptionService::ALREADY_PUBLISHED);
        }

        if ($obItem->getAccess() === 'M') {
            if (!$obUser->isRole('ADMIN')) {
                ExceptionService::getException(ExceptionService::NO_ACCESS_EDIT);
            }
        }

        try {
            $obItem->setAccess($obUser->isRole('ADMIN') ? 'O' : 'M');

            $obItem->setAuthor(static::$obUserRepository->find($obUser->getId()));

            static::$obEntityManager->persist($obItem);
            static::$obEntityManager->flush();
            static::$obEntityManager->refresh($obItem);
        } catch (Exception $obException) {
            ExceptionService::pushException($obException);
        }

        return $obItem->makeRestrict($obUser);
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
                ExceptionService::getException(ExceptionService::NO_ACCESS_EDIT);
            }
        } else {
            ExceptionService::getException(ExceptionService::NO_ACCESS_EDIT);
        }

        return true;
    }

    /**
     * @param int                $id
     * @param \App\Entity\TUsers $obUser
     * @return Object|null
     */
    public function getVisibleByID(int $id, UserInterface $obUser): ?object
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

    /**
     * @param TUsers|UserInterface $obUser
     * @param array                $arParams
     * @return \ArrayIterator|\Traversable
     */
    public function getVisibleForUser(TUsers $obUser, array $arParams = [])
    {
         $dbQuery = static::buildFilterBase($this->createQueryBuilder('t'), $obUser, $arParams)
            ->orderBy('t.id', 'DESC')
            ->getQuery();

        if (!array_key_exists('page', $arParams) || $arParams['page'] < 1) {
            $arParams['page'] = 1;
        }

        if (!array_key_exists('pageCount', $arParams) || $arParams['pageCount'] < 1) {
            $arParams['pageCount'] = 20;
        }

        $paginator = new Paginator($dbQuery);

        $arParams['pageCount'] = min($arParams['pageCount'], 100);

        $paginator
            ->getQuery()
            ->setFirstResult($arParams['pageCount'] * ($arParams['page'] - 1))
            ->setMaxResults($arParams['pageCount']);

        return $paginator->getIterator();
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @param array                $arParams
     * @return object[] Returns an array of objects
     */
    public function getOwnedByUser(TUsers $obUser, array $arParams = []): array
    {
        return static::buildFilterBase($this->createQueryBuilder('t'), $obUser, $arParams)
            ->andWhere('t.author = :user_id')
            ->setParameter('user_id', $obUser->getId())
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param QueryBuilder $obBuilder
     * @param TUsers       $obUser
     * @param array        $arParams
     * @return QueryBuilder
     * @noinspection PhpUnusedParameterInspection
     */
    protected function buildFilterLocal(QueryBuilder $obBuilder, TUsers $obUser, array $arParams = []): QueryBuilder
    {
        return $obBuilder;
    }

    /**
     * @param QueryBuilder $obBuilder
     * @param TUsers       $obUser
     * @param array        $arParams
     * @return QueryBuilder
     */
    protected function buildFilterBase(QueryBuilder $obBuilder, TUsers $obUser, array $arParams = []): QueryBuilder
    {
        $obBuilder->andWhere('t.author = :user OR t.access IN (:access)')
            ->setParameter('user', $obUser->getId())
            ->setParameter('access', $obUser->getAccessViews());

        if (array_key_exists('access', $arParams)) {
            if (!is_array($arParams['access'])) {
                $arParams['access'] = [$arParams['access']];
            }
            $arParams['access'] = array_filter($arParams['access']);

            if (count($arParams['access'])) {
                $arParams['access'] = array_intersect(
                    $arParams['access'],
                    array_keys(IngredientRepository::ACCESS_STATUS)
                );
                $obBuilder->andWhere('t.access IN (:accessF)')
                    ->setParameter('accessF', $arParams['access']);
            }
        }

        if (array_key_exists('filter', $arParams)) {
            foreach ($arParams['filter'] as $strKey => $strVal) {
                $obBuilder
                    ->andWhere('t.' . $strKey . ' = :p_' . $strKey)
                    ->setParameter('p_' . $strKey, $strVal);
            }
        }

        return $this->buildFilterLocal($obBuilder, $obUser, $arParams);
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return object[] Returns an array of objects
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
     * @param string               $name
     * @param TUsers|UserInterface $obUser
     * @return object[] Returns an array of objects
     */
    public function findByName(string $name, TUsers $obUser): array
    {

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) LIKE :val AND (t.author = :user OR t.access = :access)')
            ->setParameter('val', mb_strtolower($name))
            ->setParameter('user', $obUser->getId())
            ->setParameter('access', 'O')
            ->setMaxResults(10)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $name
     * @param TUsers $obUser
     * @return object|null Returns an object
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName(string $name, TUsers $obUser): ?object
    {

        $name = addcslashes($name, '%');

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) LIKE :val AND (t.author = :user OR t.access = :access)')
            ->setParameter('val', mb_strtolower($name))
            ->setParameter('access', 'O')
            ->setParameter('user', $obUser->getId())
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
