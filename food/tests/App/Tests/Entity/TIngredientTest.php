<?php

namespace App\Tests\Entity;

use App\Entity\TIngredient;
use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use App\Tests\Helpers;
use Exception;
use PHPUnit\Framework\TestCase;

class TIngredientTest extends TestCase
{

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameOK(): void
    {
        $strName = Helpers::getRandString();

        $obIngredient = new TIngredient();
        $obIngredient->setName($strName);
        $this->assertSame($strName, $obIngredient->getName());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameEmpty(): void
    {
        $obIngredient = new TIngredient();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_EMPTY['code']);
        $obIngredient->setName('');
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameShort(): void
    {
        $strName = Helpers::getRandString(2);

        $obIngredient = new TIngredient();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_SHORT['code']);
        $obIngredient->setName($strName);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetMinimumEmpty(): void
    {
        $obIngredient = new TIngredient();
        $this->expectExceptionCode(ExceptionService::INGREDIENT_MINIMUM['code']);
        $obIngredient->setMinimum(0);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetMinimumOK(): void
    {
        $obIngredient = new TIngredient();
        $obIngredient->setMinimum(5);
        $this->assertSame(5.0, $obIngredient->getMinimum());
    }

    /**
     * @return void
     */
    public function testMakeRestrict(): void
    {
        $obIngredient = new TIngredient();
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
        $obIngredient = new TIngredient();
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
