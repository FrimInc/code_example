<?php

namespace App\Repository;

use App\Entity\TShopList;
use App\Entity\TUsers;
use App\Exceptions\FieldValidateException;
use App\Repository\General\AccessProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method TShopList|null find($id, $lockMode = null, $lockVersion = null)
 * @method TShopList|null findOneBy(array $criteria, array $orderBy = null)
 * @method TShopList[]    findAll()
 * @method TShopList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopListRepository extends AccessProvider
{

    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * ShopList constructor.
     *
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TShopList::class);
        static::$obEntityManager  = $obEntityManager;
        static::$obUserRepository = new UserRepository($obRegistry);
    }

    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TShopList id
     * @throws FieldValidateException
     * @throws \Exception
     */
    public function put($arFields, TUsers $obUser): ?TShopList
    {

        if (array_key_exists('id', $arFields) && $arFields['id'] > 0) {
            $obNewShopList = $this->getVisibleByID($arFields['id'], $obUser);
            if (!$obNewShopList || !$obNewShopList->makeRestrict($obUser)->getCanEdit()) {
                throw new Exception('Список покупок не найден или у вас нет к нему доступа');
            }
        } elseif (array_key_exists('main', $arFields)) {
            $arNewShopList = $this->getVisibleForUser($obUser, ['filter' => ['main' => true]]);
            $obNewShopList = false;
            if (count($arNewShopList) > 0) {
                $obNewShopList = $arNewShopList[0];
            }
            if (!$obNewShopList || !$obNewShopList->makeRestrict($obUser)->getCanEdit()) {
                throw new Exception('Список покупок не найден или у вас нет к нему доступа');
            }
        } else {
            $obNewShopList = $this->getEmpty($obUser);
            $obNewShopList
                ->setName($arFields['name']);
        }

        $obNewShopList
            ->setList(
                $arFields['list'],
                array_key_exists('merge', $arFields) ? $obNewShopList->getList() : []
            );

        static::$obEntityManager->persist($obNewShopList);
        static::$obEntityManager->flush();

        return $obNewShopList;
    }

    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TShopList
     * @throws Exception
     */
    public function check($arFields, TUsers $obUser): TShopList
    {
        $obNewShopList = $this->getEmpty($obUser);

        if ($arFields['id'] && !($obNewShopList = $this->getVisibleByID($arFields['id'], $obUser))) {
            throw new Exception('Список покупок не найден или у вас нет к нему доступа');
        }

        $obNewShopList
            ->setId($arFields['id'])
            ->setName($arFields['name'])
            ->setList($arFields['list']);

        return $obNewShopList;
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws Exception
     */
    public function delete(int $intId, TUsers $obUser): bool
    {

        if ($obDeleteShopList = $this->find($intId)) {
            if ($obDeleteShopList->makeRestrict($obUser)->getCanEdit()) {
                static::$obEntityManager->remove($obDeleteShopList);
                static::$obEntityManager->flush();
                return true;
            }
            throw new Exception('У вас нет доступа к этому рецепту');
        }
        return false;
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws Exception
     */
    public function setMain(int $intId, TUsers $obUser): bool
    {

        foreach ($this->getOwnedByUser($obUser, ['filter' => ['main' => true]]) as $obIsCurrent) {
            $obIsCurrent->setMain(false);
            static::$obEntityManager->persist($obIsCurrent);
        }

        if ($obNewMainShopList = $this->find($intId)) {
            if ($obNewMainShopList->makeRestrict($obUser)->getCanEdit()) {
                $obNewMainShopList->setMain(true);
                static::$obEntityManager->persist($obNewMainShopList);
                static::$obEntityManager->flush();
                return true;
            }
            throw new Exception('У вас нет доступа к этому рецепту');
        }
        return false;
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return TShopList|null
     * @throws FieldValidateException
     */
    public function getEmpty(TUsers $obUser): ?TShopList
    {
        $obShopList = new TShopList();

        try {
            $obShopList
                ->setName('Новый список покупок ' . date('d.m.Y'))
                ->setAuthor($obUser)
                ->setList([])
                ->setId(0)
                ->setMain(false)
                ->setAccess('P');
        } catch (Exception $eException) {
            throw $eException;
        }

        return $obShopList;
    }
}
