<?php

namespace App\Exceptions;

use Exception;

class ExceptionFactory
{

    //GENERAL
    public const   NO_ACCESS_EDIT = ['code' => 1000, 'message' => 'Доступ запрещен'];
    public const   NOT_FOUND      = ['code' => 1001, 'message' => 'Элемент не найден'];

    //AUTH
    public const   REGISTRATION_CLOSED    = ['code' => 1, 'message' => 'Регистрация закрыта'];
    public const   EMAIL_EMPTY            = ['code' => 2, 'message' => 'Введите Email'];
    public const   YOU_ROBOT              = ['code' => 3, 'message' => 'Похоже, что вы робот'];
    public const   EMAIL_SHORT            = ['code' => 4, 'message' => 'Слишком короткий логин'];
    public const   USER_EXISTS            = ['code' => 5, 'message' => 'Емеил занят. Возвожно это вы?'];
    public const   PASSWORD_NOT_CONFIRMED = ['code' => 6, 'message' => 'Введённые пароли не совпадают'];

    public const   PASSWORD_SHORT = ['code' => 7, 'message' => 'Пароль должен быть не менее 8 символов длинной'];
    public const   NAME_EMPTY     = ['code' => 8, 'message' => 'Введите имя'];
    public const   EMAIL_INVALID  = ['code' => 9, 'message' => 'Неверный Email'];

    //FieldValidation
    public const   INGREDIENT_NAME_EMPTY  = ['code' => 10, 'message' => 'Название ингредиента не может быть пустым'];
    public const   INGREDIENT_NAME_SHORT  = ['code' => 11, 'message' => 'Название ингредиента слишком короткое'];
    public const   INGREDIENT_MINIMUM     = ['code' => 12, 'message' => 'Минимум не может быть меньше 1'];
    public const   INGREDIENT_TYPE        = ['code' => 13, 'message' => 'Укажите тип'];
    public const   INGREDIENT_UNITS       = ['code' => 14, 'message' => 'Укажите единицу измерения'];
    public const   INGREDIENT_NAME_EXISTS = ['code' => 15, 'message' => 'Ингредиент с таким названием уже существует'];

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
            throw new $strClass($arData['message'] . ($strComment ? (': ' . $strComment) : ''), $arData['code']);
        } else {
            throw new $strClass('Неизвестная ошибка: ' . print_r([$arData, $strComment], true), 999);
            //TODO log ex
        }
    }

    /**
     * @param Exception $eException
     * @return void
     * @throws \Exception
     */
    public static function pushException(Exception $eException): void
    {
        //TODO log Here


        throw $eException;
    }
}
