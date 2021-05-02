<?php

namespace Ipol\Core;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\CompanyTable;
use Bitrix\Sale\Order;
use CCatalogProduct;
use CModule;

class OrderHelper
{

    /**
     * @param $orderID
     * @return void
     */
    public static function setShipmentFields($orderID)
    {

        CModule::IncludeModule('main');
        CModule::IncludeModule('iblock');
        CModule::IncludeModule('catalog');
        CModule::IncludeModule('sale');

        $order = Order::load($orderID);
        foreach ($order->getShipmentCollection() as $shipment) {
            if (!$shipment->isSystem()) {
                if (
                    $shipment->getField('DEDUCTED') != 'Y' &&
                    $shipment->getField('STATUS_ID') != Constants::STATUS_SHIPPED
                ) {
                    $shipment->setField('DELIVERY_DOC_DATE', new DateTime());
                    $shipment->setField('DEDUCTED', 'Y');
                    $shipment->setField('STATUS_ID', Constants::STATUS_SHIPPED);
                    $shipment->save();
                    Versionning::WriteNewVersion($order);
                }
            }
        }
    }

    /**
     * @param $orderID
     * @return void
     */
    public static function syncOrderShipmentAndBasket($orderID)
    {
        if ($orderID == 78538) {
            return;
        }

        CModule::IncludeModule('main');
        CModule::IncludeModule('iblock');
        CModule::IncludeModule('catalog');
        CModule::IncludeModule('sale');

        if (!($order = Order::load($orderID))) {
            return;
        }
        if (!($shipment = $order->getShipmentCollection()[0])) {
            return;
        }

        $systemShipment = false;
        if ($shipment->isSystem()) {
            $systemShipment = $shipment;
            if (!($shipment = $order->getShipmentCollection()[1])) {
                return;
            }
        }

        $systemShipmentItemCollection = false;
        if (!$systemShipment) {
            $systemShipment               = $shipment->getCollection()->getSystemShipment();
            $systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();
        }

        $arShipmentProducts = [];
        $obShipmentProducts = [];

        if ($shipment->getFieldValues()['STATUS_ID'] == Constants::STATUS_PCOMPL) {
            return;
        }

        $obSystemShipmentProducts = [];

        foreach ($shipment->getShipmentItemCollection() as $obItem) {
            $arShipmentProducts[$obItem->getBasketId()] = $obItem->getQuantity();
            $obShipmentProducts[$obItem->getBasketId()] = $obItem;
            if ($systemShipmentItemCollection) {
                $obSystemShipmentProducts[$obItem->getBasketId()]
                    = $systemShipmentItemCollection->getItemByBasketCode($obItem->getBasketCode());
                if ($obSystemShipmentProducts[$obItem->getBasketId()]) {
                    $obSystemShipmentProducts[$obItem->getBasketId()]->setFieldNoDemand('QUANTITY', 0);
                    $obSystemShipmentProducts[$obItem->getBasketId()]->Save();
                }
            }
        }

        $traceQuantity = $shipment->GetField('ALLOW_DELIVERY') == 'Y';

        $arBasketProducts = [];
        $obBasketProducts = [];

        foreach ($order->getBasket() as $basketItem) {
            if ($basketItem->getField['SET_PARENT_ID']) {
                continue;
            }

            $arBasketProducts[$basketItem->getId()] = $basketItem->getQuantity();
            $obBasketProducts[$basketItem->getId()] = $basketItem;
        }

        $arBasketProducts = array_filter($arBasketProducts);
        $obBasketProducts = array_filter($obBasketProducts);

        $needSave = false;
        foreach ($arBasketProducts as $intBasketID => $productQuantity) {
            if (!$obBasketProducts[$intBasketID]) {
                continue;
            }

            if (!array_key_exists($intBasketID, $arShipmentProducts)) {
                if ($item = $shipment->getShipmentItemCollection()->createItem($obBasketProducts[$intBasketID])) {
                    $item->SetField('QUANTITY', $productQuantity);
                    $item->SetField('RESERVED_QUANTITY', $productQuantity);
                    $item->SetQuantity($productQuantity);

                    if ($traceQuantity) {
                        $arCa = CCatalogProduct::GetByID($obBasketProducts[$intBasketID]->getProductId());
                        CCatalogProduct::Update(
                            $obBasketProducts[$intBasketID]->getProductId(),
                            [
                                'QUANTITY' => $arCa['QUANTITY'] - $productQuantity
                            ]
                        );
                    }

                    $item->Save();
                    if ($obSystemShipmentProducts[$intBasketID]) {
                        $obSystemShipmentProducts[$intBasketID]->setFieldNoDemand('QUANTITY', 0);
                        $obSystemShipmentProducts[$intBasketID]->Save();
                    }
                    $needSave = true;
                }
            } elseif ($arShipmentProducts[$intBasketID] != $productQuantity || defined('FORCE')) {
                $obShipmentProducts[$intBasketID]->SetField('QUANTITY', $productQuantity);
                $obShipmentProducts[$intBasketID]->SetField('RESERVED_QUANTITY', $productQuantity);
                $obShipmentProducts[$intBasketID]->SetQuantity($productQuantity);

                if ($traceQuantity) {
                    $arCa = CCatalogProduct::GetByID($obBasketProducts[$intBasketID]->getProductId());
                    CCatalogProduct::Update(
                        $obBasketProducts[$intBasketID]->getProductId(),
                        [
                            'QUANTITY' => $arCa['QUANTITY'] - ($productQuantity - $arShipmentProducts[$intBasketID])
                        ]
                    );
                }

                $obShipmentProducts[$intBasketID]->Save();
                if ($obSystemShipmentProducts[$intBasketID]) {
                    $obSystemShipmentProducts[$intBasketID]->setFieldNoDemand('QUANTITY', 0);
                    $obSystemShipmentProducts[$intBasketID]->Save();
                }
                $needSave = true;
            }
        }

        foreach ($arShipmentProducts as $intBasketID => $productQuantity) {
            if (
                (!array_key_exists($intBasketID, $arBasketProducts) || !$arBasketProducts[$intBasketID])
                && $obBasketProducts[$intBasketID] // пропускаем состав комплектов
            ) {
                $obShipmentProducts[$intBasketID]->tryUnreserve();
                $obShipmentProducts[$intBasketID]->SetField('QUANTITY', 0);
                $obShipmentProducts[$intBasketID]->SetField('RESERVED_QUANTITY', 0);
                $obShipmentProducts[$intBasketID]->SetQuantity(0);
                if ($traceQuantity) {
                    $arCa = CCatalogProduct::GetByID($obBasketProducts[$intBasketID]->getProductId());
                    CCatalogProduct::Update(
                        $obBasketProducts[$intBasketID]->getProductId(),
                        [
                            'QUANTITY' => $arCa['QUANTITY'] + $productQuantity
                        ]
                    );
                }
                if ($obSystemShipmentProducts[$intBasketID]) {
                    $obSystemShipmentProducts[$intBasketID]->setFieldNoDemand('QUANTITY', 0);
                    $obSystemShipmentProducts[$intBasketID]->Save();
                }
                $obShipmentProducts[$intBasketID]->Save();
                $needSave = true;
            }
        }

        if ($needSave) {
            $shipment->Save();
        }

        foreach ($shipment->getShipmentItemCollection() as $obItem) {
            if ($systemShipmentItemCollection) {
                $obSystemShipmentProducts[$obItem->getBasketId()]
                    = $systemShipmentItemCollection->getItemByBasketCode($obItem->getBasketCode());
                if ($obSystemShipmentProducts[$obItem->getBasketId()]) {
                    $obSystemShipmentProducts[$obItem->getBasketId()]->setFieldNoDemand('QUANTITY', 0);
                    $obSystemShipmentProducts[$obItem->getBasketId()]->Save();
                }
            }
        }
    }

    /**
     * @param $orderID
     * @return false
     */
    public static function getOrderByID($orderID)
    {
        CModule::IncludeModule('sale');
        try {
            $order = Order::load($orderID);
            if (!$order->GetID()) {
                return false;
            }

            $order->PROPS = [];
            foreach ($order->getPropertyCollection() as $property) {
                $order->PROPS[$property->getField('CODE')]   = $property->getValue();
                $order->A_PROPS[$property->getField('CODE')] = $property;
            }
            $order->FIELDS = [];
            foreach ($order->getAllFields() as $fieldCode) {
                $order->FIELDS[$fieldCode] = $order->GetField($fieldCode) . "";
            }

            return $order;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @return bool
     */
    public function addOrderQuality()
    {
        if (
            (
            !(preg_match("/\/bitrix\/admin\/sale_order_detail.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_view.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_edit.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_shipment_edit.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_edit_new.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_payment_edit.php/", $_SERVER['PHP_SELF']) ||
                preg_match("/\/bitrix\/admin\/sale_order_payment_edit_simple.php/", $_SERVER['PHP_SELF'])
            )
            ) ||
            !CModule::includeModule('sale')
        ) {
            return false;
        }

        if (!defined('NO_SCRIPT')) {
            define('NO_SCRIPT', true);
        }

        if (preg_match("/\/bitrix\/admin\/sale_order_shipment_edit.php/", $_SERVER['PHP_SELF'])) {
            include_once realpath(__DIR__ . '/../admin/shipment_scripts.php');
        } elseif (
            preg_match("/\/bitrix\/admin\/sale_order_payment_edit.php/", $_SERVER['PHP_SELF']) ||
            preg_match("/\/bitrix\/admin\/sale_order_payment_edit_simple.php/", $_SERVER['PHP_SELF'])
        ) {
            include_once realpath(__DIR__ . '/../admin/payment_scripts.php');
        } else {
            include_once realpath(__DIR__ . '/../admin/order_scripts.php');
            include_once realpath(__DIR__ . '/../admin/order_quality.php');
        }
        return true;
    }

    /**
     * @param $tableName
     * @return int|mixed
     */
    public static function getTableID($tableName)
    {
        if (!$tableName) {
            return -1;
        }
        global $DB;
        return $DB->Query("select * from b_hlblock_entity where TABLE_NAME ='" . $tableName . "'")->Fetch()['ID'] ?: -1;
    }

    /**
     * @param int $HL_ID
     * @return mixed
     * @throws \Exception
     */
    public function getHLObject($HL_ID = -1)
    {

        if (!is_numeric($HL_ID) && $HL_ID && is_string($HL_ID)) {
            $HL_ID = self::getTableID($HL_ID);
        }

        if ($HL_ID < 1) {
            throw new \Exception("WRONG_HL_ID", 1);
        }

        if (!($hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($HL_ID)->fetch())) {
            throw new \Exception("WRONG_HL_ID_OR_NOT_FOUND", 2);
        }

        $entity      = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $obObject = new $entityClass();

        return $obObject;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getUFaces()
    {

        $obFaces = self::GetHLObject('ipol_uface_list');
        $dbFaces = $obFaces->GetList();

        $arResult = [];


        $dbCompany    = CompanyTable::getList(['select' => ['ID', 'NAME', 'UF_UFACE']]);
        $face2company = [];

        while ($arCompany = $dbCompany->Fetch()) {
            $face2company[$arCompany['UF_UFACE']][] = $arCompany['ID'];
        }

        while ($arFace = $dbFaces->Fetch()) {
            $arResult[$arFace['ID']] = [
                'NAME'      => $arFace['UF_NAME'],
                'PREFIX'    => $arFace['UF_PREF'],
                'COMPANIES' => $face2company[$arFace['ID']]
            ];
        }

        return $arResult;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequsistes()
    {

        $obFaces = self::GetHLObject('ipol_req');

        $arResult = [];

        $dbCompany = $obFaces::getList();
        while ($arCompany = $dbCompany->Fetch()) {
            $arResult[$arCompany['ID']] = $arCompany;
        }

        return $arResult;
    }
}
