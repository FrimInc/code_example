<?php

namespace App\Tests\Entity;

use App\Entity\TRecipe;
use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use App\Tests\Helpers;
use Exception;
use PHPUnit\Framework\TestCase;

class TRecipeTest extends TestCase
{

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameOK(): void
    {
        $strName = Helpers::getRandString();

        $obRecipe = new TRecipe();
        $obRecipe->setName($strName);
        $this->assertSame($strName, $obRecipe->getName());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameEmpty(): void
    {
        $obRecipe = new TRecipe();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_EMPTY['code']);
        $obRecipe->setName('');
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameShort(): void
    {
        $strName = Helpers::getRandString(2);

        $obRecipe = new TRecipe();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_SHORT['code']);
        $obRecipe->setName($strName);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetAnounce(): void
    {
        $obRecipe    = new TRecipe();
        $strAnnounce = Helpers::getRandString();
        $obRecipe->setAnounce($strAnnounce);

        $this->assertSame($strAnnounce, $obRecipe->getAnounce());

        $this->expectExceptionCode(ExceptionService::RECIPE_ANNOUNCE_EMPTY['code']);
        $obRecipe->setAnounce('');
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetStages(): void
    {
        $obRecipe = new TRecipe();

        $arStages = [
            Helpers::getRandString(),
            Helpers::getRandString(),
            Helpers::getRandString()
        ];

        $obRecipe->setStages($arStages);
        $this->assertSame($arStages, $obRecipe->getStages());

        $this->expectExceptionCode(ExceptionService::RECIPE_STAGES_EMPTY['code']);
        $obRecipe->setStages([]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDifficult(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setDifficult(1);
        $this->assertSame(1, $obRecipe->getDifficult());

        $obRecipe->setDifficult(2);
        $this->assertSame(2, $obRecipe->getDifficult());

        $obRecipe->setDifficult(3);
        $this->assertSame(3, $obRecipe->getDifficult());

        $obRecipe->setDifficult(4);
        $this->assertSame(4, $obRecipe->getDifficult());

        $obRecipe->setDifficult(5);
        $this->assertSame(5, $obRecipe->getDifficult());

        $this->expectExceptionCode(ExceptionService::RECIPE_DIFFICULTY_WRONG['code']);
        $obRecipe->setDifficult(0.5);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDifficultLow(): void
    {
        $obRecipe = new TRecipe();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(ExceptionService::RECIPE_DIFFICULTY_WRONG['code']);
        $obRecipe->setDifficult(0);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDifficultHight(): void
    {
        $obRecipe = new TRecipe();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(ExceptionService::RECIPE_DIFFICULTY_WRONG['code']);
        $obRecipe->setDifficult(6);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetServing(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setServing(1);
        $this->assertSame(1, $obRecipe->getServing());

        $obRecipe->setServing(10);
        $this->assertSame(10, $obRecipe->getServing());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setServing(0);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDays(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setDays(1);
        $this->assertSame(1, $obRecipe->getDays());

        $obRecipe->setDays(10);
        $this->assertSame(10, $obRecipe->getDays());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setDays(-1);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDaysEx1(): void
    {
        $obRecipe = new TRecipe();

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setDays(-1);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDaysEx2(): void
    {
        $obRecipe = new TRecipe();

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setDays(100);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetActiveTime(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setActiveTime(1);
        $this->assertSame(1, $obRecipe->getActiveTime());

        $obRecipe->setActiveTime(10);
        $this->assertSame(10, $obRecipe->getActiveTime());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setActiveTime(-1);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetTotalTime(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setTotalTime(1);
        $this->assertSame(1, $obRecipe->getTotalTime());

        $obRecipe->setTotalTime(10);
        $this->assertSame(10, $obRecipe->getTotalTime());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setTotalTime(-1);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetKkal(): void
    {
        $obRecipe = new TRecipe();

        $obRecipe->setKkal(1);
        $this->assertSame(1, $obRecipe->getKkal());

        $obRecipe->setKkal(10);
        $this->assertSame(10, $obRecipe->getKkal());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obRecipe->setKkal(-1);
    }

    /**
     * @return void
     */
    public function testMakeRestrict(): void
    {
        $obRecipe     = new TRecipe();
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

        $obRecipe->setAuthor($obUserAuthor);

        $obRecipe->setAccess('P');
        $obRecipe->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(true, $obRecipe->getCanEdit());
        $this->assertSame(true, $obRecipe->getCanDelete());
        $this->assertSame(true, $obRecipe->getCanPublish());

        $obRecipe->setAccess('M');
        $obRecipe->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(false, $obRecipe->getCanEdit());
        $this->assertSame(false, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('O');
        $obRecipe->makeRestrict($obUserAuthor);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(false, $obRecipe->getCanEdit());
        $this->assertSame(false, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('P');
        $obRecipe->makeRestrict($obUserOther);
        $this->assertSame(false, $obRecipe->getIsMine());
        $this->assertSame(false, $obRecipe->getCanEdit());
        $this->assertSame(false, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('M');
        $obRecipe->makeRestrict($obUserOther);
        $this->assertSame(false, $obRecipe->getIsMine());
        $this->assertSame(false, $obRecipe->getCanEdit());
        $this->assertSame(false, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('O');
        $obRecipe->makeRestrict($obUserOther);
        $this->assertSame(false, $obRecipe->getIsMine());
        $this->assertSame(false, $obRecipe->getCanEdit());
        $this->assertSame(false, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('P');
        $obRecipe->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(true, $obRecipe->getCanEdit());
        $this->assertSame(true, $obRecipe->getCanDelete());
        $this->assertSame(true, $obRecipe->getCanPublish());

        $obRecipe->setAccess('M');
        $obRecipe->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(true, $obRecipe->getCanEdit());
        $this->assertSame(true, $obRecipe->getCanDelete());
        $this->assertSame(true, $obRecipe->getCanPublish());

        $obRecipe->setAccess('O');
        $obRecipe->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(true, $obRecipe->getCanEdit());
        $this->assertSame(true, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());

        $obRecipe->setAccess('O');
        $obRecipe->makeRestrict($obUserAdmin);
        $this->assertSame(true, $obRecipe->getIsMine());
        $this->assertSame(true, $obRecipe->getCanEdit());
        $this->assertSame(true, $obRecipe->getCanDelete());
        $this->assertSame(false, $obRecipe->getCanPublish());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCanDeleteOK(): void
    {
        $obRecipe     = new TRecipe();
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

        $obRecipe->setAuthor($obUserAuthor);

        ////////// PRIVATE
        $obRecipe->setAccess('P');
        $obException = false;
        try {
            $boolRes = $obRecipe->checkCanEdit($obUserAuthor);
            $this->assertSame(true, $boolRes);
            $boolRes = $obRecipe->checkCanDelete($obUserAuthor);
            $this->assertSame(true, $boolRes);

            $boolRes = $obRecipe->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obRecipe->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);

        $obException = false;
        try {
            $obRecipe->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        ////////// MODERATE
        $obRecipe->setAccess('M');

        $obException = false;
        try {
            $obRecipe->checkCanEdit($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanDelete($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $boolRes = $obRecipe->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obRecipe->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);

        ////////// PUBLISHED
        $obRecipe->setAccess('O');

        $obException = false;
        try {
            $obRecipe->checkCanEdit($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanDelete($obUserAuthor);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanEdit($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $obRecipe->checkCanDelete($obUserOther);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertInstanceOf(Exception::class, $obException);
        $this->assertSame(ExceptionService::NO_ACCESS_EDIT['code'], $obException->getCode());

        $obException = false;
        try {
            $boolRes = $obRecipe->checkCanEdit($obUserAdmin);
            $this->assertSame(true, $boolRes);
            $boolRes = $obRecipe->checkCanDelete($obUserAdmin);
            $this->assertSame(true, $boolRes);
        } catch (Exception $obEx) {
            $obException = $obEx;
        }
        $this->assertSame(false, $obException);
    }
}
