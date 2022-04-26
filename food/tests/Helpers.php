<?php

namespace App\Tests;

class Helpers
{

    /**
     * @param int $intLength
     * @return string
     */
    public static function getRandString(int $intLength = 10): string
    {
        return substr(md5(time() . microtime(1) . rand(11111, 99999)), 0, $intLength);
    }


    /**
     * @return string
     */
    public static function getRandEmail(): string
    {
        return self::getRandString() . '@fri-m.ru';
    }
}
