<?php

namespace App\Tests\Entity;

use App\Entity\TShopList;
use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use Exception;
use PHPUnit\Framework\TestCase;

class TShopListTest extends TestCase
{

    /**
     * @return void
     */
    public function testMakeRestrict(): void
    {
        $obIngredient = new TShopList();
        $obUserAuthor = new TUsers();
        $obUserAuthor
            ->setId(1)
            ->setRoles(['ROLE_USER']);

        $obUserOther = new TUsers();
        $obUserOther
            ->setId(2)
            ->setRoles(['ROLE_USER']);

        $obUserAdmin = new TUsers();
        $obUserAdmin
            ->setId(3)
            ->setRoles(['ROLE_ADMIN']);

        $obIngredient->setAuthor($obUserAuthor);

        $obIngredient->setAccess('P');
        $obIngredient->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(true, $obIngredient->getCanEdit());
        $this->assertSame(true, $obIngredient->getCanDelete());
        $this->assertSame(true, $obIngredient->getCanPublish());

        $obIngredient->setAccess('M');
        $obIngredient->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(false, $obIngredient->getCanEdit());
        $this->assertSame(false, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('O');
        $obIngredient->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(false, $obIngredient->getCanEdit());
        $this->assertSame(false, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('P');
        $obIngredient->makeRestrict($obUserOther);
        $this->assertSame(false, $obIngredient->getIsMine());
        $this->assertSame(false, $obIngredient->getCanEdit());
        $this->assertSame(false, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('M');
        $obIngredient->makeRestrict($obUserOther);
        $this->assertSame(false, $obIngredient->getIsMine());
        $this->assertSame(false, $obIngredient->getCanEdit());
        $this->assertSame(false, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('O');
        $obIngredient->makeRestrict($obUserOther);
        $this->assertSame(false, $obIngredient->getIsMine());
        $this->assertSame(false, $obIngredient->getCanEdit());
        $this->assertSame(false, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('P');
        $obIngredient->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(true, $obIngredient->getCanEdit());
        $this->assertSame(true, $obIngredient->getCanDelete());
        $this->assertSame(true, $obIngredient->getCanPublish());

        $obIngredient->setAccess('M');
        $obIngredient->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(true, $obIngredient->getCanEdit());
        $this->assertSame(true, $obIngredient->getCanDelete());
        $this->assertSame(true, $obIngredient->getCanPublish());

        $obIngredient->setAccess('O');
        $obIngredient->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(true, $obIngredient->getCanEdit());
        $this->assertSame(true, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());

        $obIngredient->setAccess('O');
        $obIngredient->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obIngredient->getIsMine());
        $this->assertSame(true, $obIngredient->getCanEdit());
        $this->assertSame(true, $obIngredient->getCanDelete());
        $this->assertSame(false, $obIngredient->getCanPublish());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanDeleteOK(): void
    {
        $obIngredient = new TShopList();
        $obUserAuthor = new TUsers();
        $obUserAuthor
            ->setId(1)
            ->setRoles(['ROLE_USER']);

        $obUserOther = new TUsers();
        $obUserOther
            ->setId(2)
            ->setRoles(['ROLE_USER']);

        $obUserAdmin = new TUsers();
        $obUserAdmin
            ->setId(3)
            ->setRoles(['ROLE_ADMIN']);

        $obIngredient->setAuthor($obUserAuthor);

        ////////// PRIVATE
        $obIngredient->setAccess('P');
        $obException = false;
        try {
            $boolRes = $obIngredient->checkCanEdit($obUserAuthor);
            $this->assertSame(true, $boolRes);
            $boolRes = $obIngredient->checkCanDelete($obUserAuthor);
            $this->assertSame(true, $boolRes);

            $boolRes = $obIngredient->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obIngredient->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);

        $obException = false;
        try {
            $obIngredient->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        ////////// MODERATE
        $obIngredient->setAccess('M');

        $obException = false;
        try {
            $obIngredient->checkCanEdit($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanDelete($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $boolRes = $obIngredient->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obIngredient->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);

        ////////// PUBLISHED
        $obIngredient->setAccess('O');

        $obException = false;
        try {
            $obIngredient->checkCanEdit($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanDelete($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obIngredient->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $boolRes = $obIngredient->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obIngredient->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);
    }
}
