<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
    <div class="ipol_tpicker_cont">
        <div class="legend">
            Выберите дату и время доставки
        </div>
        <div class="picker"
             data-input="<?= $arParams['DATE_TO'] ?>"
             data-all_div="<?= $arParams['all_div'] ?>"
             data-date_div="<?= $arParams['date_div'] ?>"
             data-time_div="<?= $arParams['time_div'] ?>">
            <?php
            $intP = -1;

            $hslots = '';

            foreach ($arResult['DATA'] as $strDay => $arSlots) {
                $intP++;
                $strSlotClass = [];

                if (count($arSlots['slots']) == 0) {
                    $strSlotClass[] = 'empty';
                } else {
                    $strSlotClass[] = 'selectable';
                }

                $strSlotClass[] = 'day_' . $arSlots['d'];

                if ($arSlots['hol']) {
                    $strSlotClass[] = 'holiday';
                }

                if ($strDay == $arResult['FIRST_DAY']) {
                    $strSlotClass[] = 'selected';
                }

                ?>
                <div data-day="<?= $strDay ?>" class="<?= implode(' ', array_merge(['day'], $strSlotClass)) ?>">
                    <div class="dname"><?= $arSlots['dn'] ?></div>
                    <div class="d_day"><?= $arSlots['dd'] ?></div>
                </div>
                <?php if (count($arSlots['slots'])) {
                    ob_start();
                    ?>
                    <div data-day="<?= $strDay ?>"
                         class="<?= implode(' ', array_merge(['slot'], $strSlotClass)) ?>">
                        <?php foreach ($arSlots['slots'] as $arSlot) { ?>
                            <label <?= $arSlot['disabled'] ? ' class="disabled" ' : '' ?>><input
                                        class="select_interval_slot"
                                        type="radio"
                                    <?= $arSlot['disabled'] ? ' disabled ' : '' ?>
                                    <?= $arSlot['default'] ? ' date-isdef ' : '' ?>
                                    <?php if (in_array('selected', $strSlotClass) && $arSlot['default']) { ?>
                                        checked
                                    <?php } ?>
                                        name="<?= $arParams['FIELD_NAME'] ?>"
                                        value="<?= $strDay . ' ' . $arSlot['slot'] ?>"/><?= $arSlot['slot'] ?></label>
                        <?php } ?>
                    </div>
                    <?php
                    $hslots .= ob_get_contents();
                    ob_end_clean();
                }
            } ?>
            <div style="clear: both; height: 1px">&nbsp;</div>
            <?= $hslots ?>
        </div>
    </div>
<?php
