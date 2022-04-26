<?php

namespace App\Tests\Entity;

use App\Entity\TIngredientType;
use App\Exceptions\ExceptionService;
use App\Tests\Helpers;
use PHPUnit\Framework\TestCase;

class TIngredientTypeTest extends TestCase
{

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameOK(): void
    {
        $strName = Helpers::getRandString();

        $obIngredientType = new TIngredientType();
        $obIngredientType->setName($strName);
        $this->assertSame($strName, $obIngredientType->getName());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameEmpty(): void
    {
        $obIngredientType = new TIngredientType();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_EMPTY['code']);
        $obIngredientType->setName('');
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetNameShort(): void
    {
        $strName = Helpers::getRandString(2);

        $obIngredientType = new TIngredientType();
        $this->expectExceptionCode(ExceptionService::ENTITY_NAME_SHORT['code']);
        $obIngredientType->setName($strName);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetSort(): void
    {
        $obIngredientType = new TIngredientType();


        $obIngredientType->setSort(1);
        $this->assertSame(1, $obIngredientType->getSort());
        $obIngredientType->setSort(10);
        $this->assertSame(10, $obIngredientType->getSort());

        $this->expectExceptionCode(ExceptionService::RANGE_ERROR['code']);
        $obIngredientType->setSort(-1);
    }
}
