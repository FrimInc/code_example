<?php

namespace App\Tests\Entity;

use App\Entity\TIngredient;
use App\Exceptions\ExceptionFactory;
use App\Exceptions\FieldValidateException;
use App\Tests\Helpers;
use PHPUnit\Framework\TestCase;

class IngredientTest extends TestCase
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
        $this->expectExceptionCode(ExceptionFactory::INGREDIENT_NAME_EMPTY['code']);
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
        $this->expectExceptionCode(ExceptionFactory::INGREDIENT_NAME_SHORT['code']);
        $obIngredient->setName($strName);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testSetMinimumEmpty(): void
    {
        $obIngredient = new TIngredient();
        $this->expectExceptionCode(ExceptionFactory::INGREDIENT_MINIMUM['code']);
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
        $this->assertSame(5, $obIngredient->getMinimum());
    }
}
