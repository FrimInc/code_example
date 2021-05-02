<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

CModule::IncludeModule('main');
CModule::IncludeModule('sale');
CJSCore::Init(array('jquery'));
$arDelivConfig = json_decode(COption::GetOptionString('main', 'deliv_conf', '{}', 's1'), true);

$_SI              = SITE_ID;
$arResult['DATA'] = array();
global $SITE_CONFIGS;

if ($SITE_CONFIGS[SITE_ID]['price_variants']) {
    $_SI .= '_' . $SITE_CONFIGS[SITE_ID]['price_code'];
}

$arResult['CONFIG'] = $arDelivConfig['CONFIG'][$_SI];
$arSlotsTotal       = array();

foreach ($arResult['CONFIG']['SLOTS'] as $intKey => $slot) {
    if ($slot['slot']) {
        $arSlotsTotal[$slot['slot']]                = array('slot_limit' => $slot['limit_orders'], 'slot_orders' => 0);
        $arResult['CONFIG']['SLOTS'][$slot['slot']] = $slot;
    }
    unset($arResult['CONFIG']['SLOTS'][$intKey]);
}
$intTime = time() + 3600 * ($arResult['CONFIG']['TIMEZONE'] - 3);

$arSlots = array();

$dateLabels = array(
    0 => '',
    1 => 'Пн',
    2 => 'Вт',
    3 => 'Ср',
    4 => 'Чт',
    5 => 'Пт',
    6 => 'Сб',
    7 => 'Вс'
);

for ($intDayKey = 0; $intDayKey <= 14; $intDayKey++) {
    $intLtime = ($intTime + $intDayKey * 86400);
    $intLday  = date('N', $intLtime);

    $arResult['DATA'][date('d.m.Y', $intLtime)] = array_merge(
        array('slots' => $arResult['CONFIG']['SLOTS']),
        array(
            'ts'  => $intTime,
            'd'   => $intLday,
            'dd'  => date('d', $intLtime),
            'dn'  => $dateLabels[$intLday],
            'hol' => $intLday > 5
        )
    );
}

$intH      = date("H", $intTime);
$intTodayd = date('d.m.Y', $intTime);

list($intHourNextDay, $intMinNextDay) = explode(':', $arResult['CONFIG']['TIME_NEXT_DAY']);

if (
    $intHourNextDay && (date("H", $intTime) >= $intHourNextDay)
    &&
    (
        !$intMinNextDay
        ||
        $intH > $intHourNextDay
        ||
        ($intH == $intHourNextDay)
        &&
        date("i", $intTime) >= $intMinNextDay
    )
) {
    unset($arResult['DATA'][$intTodayd]);
} else {
    foreach ($arResult['DATA'][$intTodayd]['slots'] as $intKey => $slot) {
        $intH = explode(":", $slot['slot'])[0];
        if ($intH >= ($intH - $slot['limit_hours'])) {
            $arResult['DATA'][$intTodayd]['slots'][$intKey]['disabled'] = true;
            $arResult['DATA'][$intTodayd]['slots'][$intKey]['default']  = 0;
        }
    }
}

foreach ($arResult['CONFIG']['HOLIDAYS'] as $intHol) {
    if ($arResult['DATA'][$intHol]) {
        $arResult['DATA'][$intHol]['slots'] = array();
    }
}

$arResult['FIRST_DAY'] = array_keys($arResult['DATA'])[0];
$arParams['VALUE']     = $arParams['VALUE'] ?: $_REQUEST[$arParams['FIELD_NAME']];
//echo '<pre>' . print_r($arParams, true) . '</pre>';
if ($arParams['VALUE']) {
    list($intCurDay, $intCurSlot) = explode(' ', $arParams['VALUE']);
    $arResult['FIRST_DAY'] = $intCurDay;
    foreach ($arResult['DATA'][$intCurDay]['slots'] as $arCS => $arCSlot) {
        $arResult['DATA'][$intCurDay]['slots'][$arCS]['default'] = ($arCS == $intCurSlot);
    }
} else {
    if ($arResult['CONFIG']['START_DATE'] && count($arResult['DATA'][$arResult['CONFIG']['START_DATE']]['slots'])) {
        $arResult['FIRST_DAY'] = $arResult['CONFIG']['START_DATE'];
    } else if ($arResult['CONFIG']['START_DAY'] && count($arResult['DATA'][$arResult['CONFIG']['START_DAY']]['slots'])) {
        $arResult['FIRST_DAY'] = array_keys($arResult['DATA'])[$arResult['CONFIG']['START_DAY']];
    }
}

$arDays = array();
for ($i = 0; $i <= 14; $i++) {
    $arDays[$d = date('d.m.Y', time() + 86400 * $i)] = array(
        'slots_limit' => $arSlotsTotal,
        'day_limit'   => $arResult['CONFIG']['DAY_LIMIT'][$d],
        'day_orders'  => 0
    );
}

$dbOrderS = CSaleOrder::GetList(
    array(),
    array(
        'LID'                                      => SITE_ID,
        '!CANCELED'                                => 'Y',
        '%PROPERTY_VAL_BY_CODE_DELIVERY_DATE_TIME' => array_keys($arDays)
    ),
    false,
    false,
    array(
        'ID',
        'PROPERTY_VAL_BY_CODE_DELIVERY_DATE_TIME',
        'LID',
        'CANCELED'
    ));


while ($arOrder = $dbOrderS->Fetch()) {
    list($intOrderDate, $intOrderTime) = explode(' ', $arOrder['PROPERTY_VAL_BY_CODE_DELIVERY_DATE_TIME']);
    $arDays[$intOrderDate]['day_orders']++;
    $arDays[$intOrderDate]['slots_limit'][$intOrderTime]['slot_orders']++;

}

foreach ($arDays as $intDayKey => $arDayParams) {
    if ($arDayParams['day_limit'] && $arDayParams['day_limit'] <= $arDayParams['day_orders']) {
        $arResult['DATA'][$intDayKey]['slots'] = array();
    } else {
        foreach ($arDayParams['slots_limit'] as $strKey => $slotParams) {
            if ($slotParams['slot_limit'] && $slotParams['slot_limit'] <= $slotParams['slot_orders']) {
                $arResult['DATA'][$intDayKey]['slots'][$strKey]['disabled'] = true;
                $arResult['DATA'][$intDayKey]['slots'][$strKey]['default']  = 0;
            }
        }
    }
}


foreach ($arResult['DATA'] as $intDayKey => &$arSlot) {
    $isEnabled = false;
    foreach ($arSlot['slots'] as $arCSlot) {
        if (!$arCSlot['disabled']) {
            $isEnabled = true;
        }
    }

    if (!$isEnabled) {
        if ($intDayKey == array_keys($arResult['DATA'])[0]) {
            unset($arResult['DATA'][$intDayKey]);
        } else {
            $arSlot['slots'] = array();
        }

    }
}

if (!$arResult['DATA'][$arResult['FIRST_DAY']]) {
    $arResult['FIRST_DAY'] = array_keys($arResult['DATA'])[0];
}


foreach ($arResult['DATA'] as $intDayKey => &$arSlot) {
    $hasDefault = false;
    foreach ($arSlot['slots'] as $strKey => $arCSlot) {
        if ($arCSlot['disabled'] && $arCSlot['default']) {
            $arSlot['slots'][$strKey]['default'] = 0;
        }

        if (!$arCSlot['disabled'] && $arCSlot['default']) {
            $hasDefault = true;
        }
    }
    if (!$hasDefault) {
        foreach ($arSlot['slots'] as $strKey => $arCSlot) {
            if (!$arCSlot['disabled']) {
                $arSlot['slots'][$strKey]['default'] = 1;
                break;
            }
        }
    }
}


$this->IncludeComponentTemplate();