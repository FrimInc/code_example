<?php

namespace App\Exceptions;

use App\Helpers\Logger;
use Exception;

class ExceptionService
{

    //GENERAL
    public const   NO_ACCESS_EDIT = ['code' => 1000, 'message' => 'Доступ запрещен'];
    public const   NOT_FOUND      = ['code' => 1001, 'message' => 'Элемент не найден'];

    //AUTH
    public const   REGISTRATION_CLOSED    = ['code' => 101, 'message' => 'Регистрация закрыта'];
    public const   EMAIL_EMPTY            = ['code' => 102, 'message' => 'Введите Email'];
    public const   YOU_ROBOT              = ['code' => 103, 'message' => 'Похоже, что вы робот'];
    public const   EMAIL_SHORT            = ['code' => 104, 'message' => 'Слишком короткий логин'];
    public const   USER_EXISTS            = ['code' => 105, 'message' => 'Емеил занят. Возвожно это вы?'];
    public const   PASSWORD_NOT_CONFIRMED = ['code' => 106, 'message' => 'Введённые пароли не совпадают'];

    public const   PASSWORD_SHORT = ['code' => 107, 'message' => 'Пароль должен быть не менее 8 символов длинной'];
    public const   NAME_EMPTY     = ['code' => 108, 'message' => 'Введите имя'];
    public const   EMAIL_INVALID  = ['code' => 109, 'message' => 'Неверный Email'];

    //FieldValidation
    public const   ENTITY_NAME_EMPTY = ['code' => 200, 'message' => 'Название ингредиента не может быть пустым'];
    public const   ENTITY_NAME_SHORT = ['code' => 201, 'message' => 'Название ингредиента слишком короткое'];
    public const   RANGE_ERROR       = ['code' => 202, 'message' => 'Значение вне диапазона'];
    public const   DIGIT_ERROR       = ['code' => 203, 'message' => 'Значение должно быть целым числом'];

    //Ingredient
    public const   INGREDIENT_MINIMUM     = ['code' => 202, 'message' => 'Минимум не может быть меньше 1'];
    public const   INGREDIENT_TYPE        = ['code' => 203, 'message' => 'Укажите тип'];
    public const   INGREDIENT_UNITS       = ['code' => 204, 'message' => 'Укажите единицу измерения'];
    public const   INGREDIENT_NAME_EXISTS = ['code' => 205, 'message' => 'Ингредиент с таким названием уже существует'];

    //Recipe
    public const   RECIPE_ANNOUNCE_EMPTY   = ['code' => 206, 'message' => 'Описание рецепта не может быть пустым'];
    public const   RECIPE_STAGES_EMPTY     = ['code' => 207, 'message' => 'Этапы рецепта должны быть заполнены'];
    public const   RECIPE_DIFFICULTY_WRONG = ['code' => 208, 'message' => 'Недопустимое значение сложности'];

    //Access
    public const   ALREADY_PUBLISHED = ['code' => 600, 'message' => 'Элемент уже опубликован'];
    public const   CANT_PUBLISH      = ['code' => 601, 'message' => 'Не удалось опубликовать'];

    private static ?ExceptionService $instance = null;

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (empty(static::$instance)) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * @param        $arData
     * @param string $strClass
     * @param string $strComment
     * @return void
     * @throws \Exception
     */
    public static function getException($arData = false, string $strClass = '\Exception', string $strComment = ''): void
    {
        if (!$strClass || !class_exists($strClass)) {
            $strClass = Exception::class;
        }
        if (is_array($arData)) {
            $eException = new $strClass(
                $arData['message'] . ($strComment ? (': ' . $strComment) : ''),
                $arData['code']
            );
        } else {
            $eException = new $strClass(
                'Неизвестная ошибка: ' . print_r([$arData, $strComment], true),
                999
            );
        }

        self::pushLogException($eException);
        throw $eException;
    }

    /**
     * @param Exception $eException
     * @return void
     * @throws \Exception
     */
    public static function pushException(Exception $eException): void
    {
        self::pushLogException($eException);
        throw $eException;
    }

    /**
     * @param Exception $eException
     * @return void
     * @throws \Exception
     */
    public static function pushLogException(Exception $eException): void
    {
        Logger::logException(
            $eException
        );
    }
}
