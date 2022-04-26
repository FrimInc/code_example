<?php

namespace App\Tools;

class ArrayObjectTools
{
    /**
     * @param array  $arObjects
     * @param string $strFieldName
     * @return array
     */
    public static function getArrayOfObjectField(array $arObjects = [], string $strFieldName = 'name'): array
    {
        $arResult = [];

        foreach ($arObjects as $obObject) {
            $arResult[] = $obObject->{'get' . $strFieldName}();
        }

        return $arResult;
    }
}
