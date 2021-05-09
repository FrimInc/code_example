<?php

namespace App\Repository\General;

use App\Entity\TUsers;
use App\Exceptions\ExceptionFactory;
use App\Repository\IngredientRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
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
                ExceptionFactory::getException(ExceptionFactory::NOT_FOUND);
            }
        } else {
            ExceptionFactory::getException(ExceptionFactory::NOT_FOUND);
        }

        if ($obItem->getAccess() === 'O') {
            ExceptionFactory::getException(ExceptionFactory::ALREADY_PUBLISHED);
        }

        if ($obItem->getAccess() === 'M') {
            if (!$obUser->isRole('ADMIN')) {
                ExceptionFactory::getException(ExceptionFactory::NO_ACCESS_EDIT);
            }
        }

        try {
            $obItem->setAccess($obUser->isRole('ADMIN') ? 'O' : 'M');

            $obItem->setAuthor(static::$obUserRepository->find($obUser->getId()));

            static::$obEntityManager->persist($obItem);
            static::$obEntityManager->flush();
            static::$obEntityManager->refresh($obItem);
        } catch (Exception $obException) {
            ExceptionFactory::pushException($obException);
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
                ExceptionFactory::getException(ExceptionFactory::NO_ACCESS_EDIT);
            }
        } else {
            ExceptionFactory::getException(ExceptionFactory::NO_ACCESS_EDIT);
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
     * @return object[] Returns an array of objects
     */
    public function getVisibleForUser(TUsers $obUser, array $arParams = []): array
    {
        return static::buildFilterBase($this->createQueryBuilder('t'), $obUser, $arParams)
            ->orderBy('t.name', 'ASC')
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
     * @param string $name
     * @param TUsers $obUser
     * @return object|null Returns an object
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName(string $name, TUsers $obUser): ?object
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
}
