<?php

namespace App\Tools;

class StringTools
{

    public static function mb_ucfirst($string)
    {
        $firstChar = mb_substr($string, 0, 1);
        $then      = mb_substr($string, 1, null);
        return mb_strtoupper($firstChar) . $then;
    }

}