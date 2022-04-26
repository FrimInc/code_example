<?php

namespace App\Repository;

use App\Entity\TMenu;
use App\Entity\TUsers;
use App\Exceptions\FieldValidateException;
use App\Repository\General\AccessProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method TMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method TMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method TMenu[]    findAll()
 * @method TMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuRepository extends AccessProvider
{

    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * @param ManagerRegistry        $obRegistry
     * @param EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $obRegistry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($obRegistry, TMenu::class);
        static::$obEntityManager  = $obEntityManager;
        static::$obUserRepository = new UserRepository($obRegistry);
    }


    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TMenu
     * @throws FieldValidateException
     * @throws Exception
     */
    public function put($arFields, TUsers $obUser): TMenu
    {
        $obNewMenu = $this->getEmpty($obUser);

        if (array_key_exists('id', $arFields) && $arFields['id']) {
            $obNewMenu = $this->find($arFields['id']);
            if (!$obNewMenu || !$obNewMenu->checkCanEdit($obUser)) {
                throw new Exception('Меню не найдено или у вас нет к нему доступа');
            }
        }

        $obNewMenu
            ->setId($arFields['id'])
            ->setName($arFields['name'])
            ->setWeek($arFields['week']);

        static::$obEntityManager->persist($obNewMenu);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewMenu);

        return $obNewMenu;
    }

    /**
     * @param array                $arFields
     * @param TUsers|UserInterface $obUser
     *
     * @return TMenu
     * @throws Exception
     */
    public function check($arFields, TUsers $obUser): TMenu
    {
        $obNewMenu = $this->getEmpty($obUser);

        if ($arFields['id'] && !($obNewMenu = $this->getVisibleByID($arFields['id'], $obUser))) {
            throw new Exception('Меню не найдено или у вас нет к нему доступа');
        }

        $obNewMenu
            ->setId($arFields['id'])
            ->setName($arFields['name'])
            ->setWeek($arFields['week']);

        return $obNewMenu;
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return TMenu
     */
    public function getEmpty(TUsers $obUser): TMenu
    {

        $obNewMenu = new TMenu();
        $obNewMenu
            ->setId(0)
            ->setAuthor($obUser)
            ->setAccess('P');

        return $obNewMenu;
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws Exception
     */
    public function delete(int $intId, TUsers $obUser): bool
    {

        if ($obDeleteMenu = $this->find($intId)) {
            $obDeleteMenu->checkCanDelete($obUser);
            static::$obEntityManager->remove($obDeleteMenu);
            static::$obEntityManager->flush();
            return true;
        }

        return false;
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @param array                $arParams
     * @return object[] Returns an array of objects
     */
    public function getCurrentForUser(TUsers $obUser, array $arParams = []): array
    {
        return static::buildFilterBase($this->createQueryBuilder('t'), $obUser, $arParams)
            ->andWhere('t.current = :current')
            ->setParameter('current', 'yes')
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int                  $intId
     * @param TUsers|UserInterface $obUser
     * @return bool
     * @throws Exception
     */
    public function setCurrent(int $intId, TUsers $obUser): bool
    {

        if (
            ($obNewCurrentMenu = $this->find($intId))
            && $obNewCurrentMenu->makeRestrict($obUser)->getIsMineReal()
        ) {
            foreach ($this->getCurrentForUser($obUser) as $obCurrentMenu) {
                $obCurrentMenu->setIsCurrent(false);
                static::$obEntityManager->persist($obCurrentMenu);
            }

            $obNewCurrentMenu->setIsCurrent(true);
            static::$obEntityManager->persist($obNewCurrentMenu);
            static::$obEntityManager->flush();
            return true;
        }

        throw new Exception('Меню не найдено или у вас нет к нему доступа');
    }
}
