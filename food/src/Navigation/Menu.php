<?php

namespace App\Navigation;

class Menu
{

    /**
     * @param string $strMenuType
     * @param string $strProjectDir
     *
     * @return array
     */
    public function getMenu($strMenuType = 'top', $strProjectDir = '/'): array
    {

        if (!file_exists($strMenuFile = $strProjectDir . '/config/.' . $strMenuType . '.menu.php')) {
            return [];
        }
        $arMenuLinks = [];

        include $strMenuFile;

        return $arMenuLinks;
    }
}
