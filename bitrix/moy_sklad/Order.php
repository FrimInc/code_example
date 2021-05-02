<?php

namespace Ipol\MS;

use Bitrix\Sale\Internals\CompanyTable;

class Order extends Transport
{

    public $strEntityType = 'customerorder';

    public $id  = null;
    public $mid = null;

    public $arOrder             = [];
    public $arCustomerOrder     = [];
    public $arCustomerShipments = [];
    public $arCustomerPayment   = [];

    public static $arStates          = [];
    public static $arBitrixStates    = [];
    public static $arBitrixCompanies = [];

    public static $props2meta = [];

    public static $obStock = null;

    /**
     * Order constructor.
     *
     * @param $order_id
     * @throws \Exception
     */
    public function __construct($order_id)
    {

        if (!self::$meta) {
            parent::__construct();
            self::$meta = $this->ms_query(self::MS_URL . $this->strEntityType . '/metadata');
            foreach (self::$meta['states'] as $k => $v) {
                self::$arStates[$v['name']] = $v['meta'];
            }

            foreach (self::$meta['attributes'] as $arAttr) {
                self::$props2meta[$arAttr['name']] = $arAttr['id'];
            }

            global $DB;

            $res = $DB->query("select STATUS_ID , NAME from b_sale_status_lang where LID='ru'");

            while ($arSt = $res->Fetch()) {
                self::$arBitrixStates[$arSt['STATUS_ID']] = $arSt['NAME'];
            }

            $dbResultList = CompanyTable::getList([
                'select' => [
                    '*', 'UF_*'
                ]
            ]);

            while ($result = $dbResultList->Fetch()) {
                self::$arBitrixCompanies[$result['ID']] = $result;
            }


            self::$obStock = new Stocks();
        }

        // Tools::g($arBitrixCompanies);
        Tools::ValidateInt($order_id, 'Номер заказа должен быть числом');

        $this->id = $order_id;

        if ($_mid = \Ipol\Core\Tools::getCache('ORD_' . $this->id)) {
            $this->mid = $_mid;
        }

        if ($this->getBitrixOrder()) {
            $this->Sync();
        }
    }


    /**
     * @param $COMPANY_ID
     * @param $PS_STATUS_DESCRIPTION
     * @return false|mixed
     * @throws \Exception
     */
    public function getOrganAcc($COMPANY_ID, $PS_STATUS_DESCRIPTION)
    {
        $arOrgan = $this->GetRandomProperty('organization', ['code=' => $PS_STATUS_DESCRIPTION ?: 1]);

        $arOrgan['ACC'] = [];
        $strHrefToGet   = $arOrgan['accounts']['meta']['href'];
        do {
            $arOrgan['ACC'] = array_merge($arOrgan['ACC'], ($arAccsResult = $this->ms_query($strHrefToGet))['rows']);
        } while ($strHrefToGet = $arAccsResult['meta']['nextHref']);

        $arAcc = false;
        if ($COMPANY_ID) {
            if ($bitrixAcc = self::$arBitrixCompanies[$COMPANY_ID]) {
                foreach ($arOrgan['ACC'] as $arAcc) {
                    if ($arAcc['accountNumber'] == $bitrixAcc['CODE']) {
                        break;
                    }
                }
            }
        }

        $arOrgan['CURR_ACC']          = $arAcc;
        $arOrgan['BITRIX_COMPANY']    = $COMPANY_ID;
        $arOrgan['AR_BITRIX_COMPANY'] = self::$arBitrixCompanies[$COMPANY_ID];

        return $arOrgan;
    }

    public $isHiddenGroup = false;

    /**
     * @throws \Exception
     */
    public function sync()
    {
        $obMsUser = new Agent($this->arOrder['FIELDS']['USER_ID']);

        $allowedDelivery = $this->arOrder['SHIPMENT'][0]->arShipment['ALLOW_DELIVERY'] == 'Y';

        $arMsOrderData = [
            'name'         => $this->id . "",
            'externalCode' => $this->id . "",
            'moment'       => $this->arOrder['FIELDS']['DATE_INSERT']->format('Y-m-d H:i:s'),
            'description'  => $this->arOrder['FIELDS']['USER_DESCRIPTION'] . "",
            'rate'         => [
                'currency' => [
                    "meta" =>
                        $this->GetRandomProperty(
                            'currency',
                            ['isoCode=' => $this->arOrder['FIELDS']['CURRENCY']]
                        )['meta']
                ]
            ],
            'applicable'   => $allowedDelivery,
            'agent'        => [
                'meta' => $obMsUser->getMeta()
            ],
            'store'        => [
                'meta' => $this->GetRandomProperty('store', [], ['code' => 1])['meta']
            ]
        ];

        if (self::$arStates[self::$arBitrixStates[$this->arOrder['FIELDS']['STATUS_ID']]]) {
            $arMsOrderData['state'] = [
                'meta' => self::$arStates[self::$arBitrixStates[$this->arOrder['FIELDS']['STATUS_ID']]]
            ];
        }

        $arOrgan = $this->GetOrganAcc(
            $this->arOrder['PAYMENT'][0]->arPayment['COMPANY_ID'],
            $this->arOrder['PAYMENT'][0]->arPayment['PS_STATUS_DESCRIPTION']
        );

        $arMsOrderData['organization'] = [
            'meta' => $arOrgan['meta']
        ];

        if ($arOrgan['CURR_ACC']) {
            $arMsOrderData['organizationAccount'] = [
                'meta' => $arOrgan['CURR_ACC']['meta']
            ];
        }

        $arMsOrderData['positions'] = [];
        $arPositions                = [];

        foreach ($this->arOrder['BASKET'] as $arObProduct) {
            if ($arObProduct['BITRIX']['SET_PARENT_ID']) {
                continue;
            }

            $arPositions[] = [
                "quantity"   => (int)$arObProduct['BITRIX']['QUANTITY'],
                "price"      => (float)($arObProduct['BITRIX']['PRICE']) * 100,
                "discount"   => 0,
                "vat"        => 0,
                "assortment" => ["meta" => $arObProduct['MS']->arProduct['meta']],
            ];
        }

        $arMsOrderData['positions'] = array_values($arPositions);

        Tools::g($arMsOrderData);

        $isNew        = false;
        $isApplicable = $arMsOrderData['applicable'];
        ////////////////////
        $this->isHiddenGroup = false;

        $arProf = @json_decode(\COption::GetOptionString('ipol.core', 'order_profiles', '{}', 's1'), true) ?: [];

        foreach ($arProf as $ConfigName => $arConf) {
            if (($arLoadConfig = @json_decode($arConf, true))) {
                if (
                    $arLoadConfig['USER_ID'] == $this->arOrder['FIELDS']['USER_ID']
                    && $arLoadConfig['USER_ID'] != 41739
                    && $arLoadConfig['USER_ID'] != 37791
                ) {
                    $this->isHiddenGroup = true;
                }
            }
        }

        if (
            $this->arOrder['FIELDS']['USER_ID'] == 64711
            ||
            $this->arOrder['FIELDS']['USER_ID'] == 64613
        ) {
            $this->isHiddenGroup = true;  //https://ipol.planfix.ru/?action=planfix&task=313941&comment=2754575
        }
        ////////////////

        foreach ($this->arOrder['PAYMENT'] as $_ => $obPayment) {
            $arOrgan             = $this->GetOrganAcc(
                $this->arOrder['PAYMENT'][$_]->arPayment['COMPANY_ID'],
                $this->arOrder['PAYMENT'][$_]->arPayment['PS_STATUS_DESCRIPTION']
            );
            $this->isHiddenGroup = $this->isHiddenGroup || $arOrgan['AR_BITRIX_COMPANY']['UF_HIDDEN_OWNER'];
        }

        if ($this->isHiddenGroup) {
            $arMsOrderData['group'] = [
                'meta' => self::$arGroups[Constants::MS_HIDDEN_GROUP_NAME]
            ];
        }

        if (!$this->getMSOrder()) {
            $isNew = true;
            $this->putItem($arMsOrderData);
        } else {
            $isApplicable = $this->arCustomerOrder['applicable'];

            unset($arMsOrderData['description']);
            unset($arMsOrderData['store']);

            $this->updateItem($arMsOrderData, 'full_order_data');
        }

        $this->getMSOrder();

        foreach ($this->arOrder['SHIPMENT'] as $obShipment) {
            $obService = new DeliveryAsProduct([
                'id'   => 'service_' . $this->arOrder['SHIPMENT'][0]->arShipment['DELIVERY_ID'],
                'name' => $this->arOrder['SHIPMENT'][0]->arShipment['DELIVERY_NAME']
            ]);

            $arDelivery = [
                "quantity"   => 1,
                "price"      => (float)($this->arOrder['SHIPMENT'][0]->arShipment['PRICE_DELIVERY']) * 100,
                "discount"   => 0,
                "vat"        => 0,
                "assortment" => ["meta" => $obService->arService['meta']],
            ];

            $obShipment->Sync($this->arCustomerOrder, $arDelivery, $arOrgan);
        }

        foreach ($this->arOrder['PAYMENT'] as $_ => $obPayment) {
            $arOrgan = $this->GetOrganAcc(
                $this->arOrder['PAYMENT'][$_]->arPayment['COMPANY_ID'],
                $this->arOrder['PAYMENT'][$_]->arPayment['PS_STATUS_DESCRIPTION']
            );

            $obPayment->isHiddenGroup = $this->isHiddenGroup;

            $obPayment->Sync($this->arCustomerOrder, $arOrgan, $this->arOrder);
        }

        $isError = false;
        if ($this->mid) {
            $arStocks = self::$obStock->getByOperation($this->mid);

            foreach ($arStocks as $arOrderPosition) {
                Tools::d([
                    'STO' => [
                        '$arOrderPosition' => $arOrderPosition,
                        '$arPositions'     => $arPositions
                    ]
                ]);

                if (
                    $arPositions[$arOrderPosition['name']]['quantity']
                    >
                    ($arOrderPosition['stock'] - $arOrderPosition['reserve'])
                ) {
                    $isError = true;
                }
            }
        }

        $arMsOrderDataUpdateAttr              = ['applicable' => $allowedDelivery];
        $arMsOrderDataUpdateAttr['positions'] = [];

        Tools::d([
            'APPLI' => [
                '$allowedDelivery' => $allowedDelivery,
                '$isNew'           => $isNew,
                '$isApplicable'    => $isApplicable,
                '$isError'         => $isError
            ]
        ]);

        if (!$allowedDelivery || $allowedDelivery && ($isNew || !$isApplicable)) {
            $arMsOrderDataUpdateAttr['attributes']
                = [
                [
                    'id'    => self::$props2meta['Ошибка'],
                    'name'  => 'Ошибка',
                    'value' => $allowedDelivery && $isError

                ]
            ];
        }

        $arPositions = [];

        foreach ($this->arOrder['BASKET'] as $arObProduct) {
            $arPositions[] = [
                "quantity"   => (int)$arObProduct['BITRIX']['QUANTITY'],
                "price"      => (float)($arObProduct['BITRIX']['PRICE']) * 100,
                "discount"   => 0,
                "reserve"    => (float)($arObProduct['BITRIX']['QUANTITY'] * $allowedDelivery),
                "vat"        => 0,
                "assortment" => ["meta" => $arObProduct['MS']->arProduct['meta']],
            ];
        }

        $arMsOrderDataUpdateAttr['positions'] = array_values($arPositions);

        $obService = new DeliveryAsProduct([
            'id'   => 'service_' . $this->arOrder['SHIPMENT'][0]->arShipment['DELIVERY_ID'],
            'name' => $this->arOrder['SHIPMENT'][0]->arShipment['DELIVERY_NAME']
        ]);

        $arMsOrderDataUpdateAttr['positions'][] = [
            "quantity"   => 1,
            "price"      => (float)($this->arOrder['SHIPMENT'][0]->arShipment['PRICE_DELIVERY']) * 100,
            "discount"   => 0,
            "reserve"    => (float)($allowedDelivery),
            "vat"        => 0,
            "assortment" => ["meta" => $obService->arService['meta']],
        ];

        $UPDA = $this->updateItem($arMsOrderDataUpdateAttr, 'short_order_data');
        $this->getMSOrder();
        Tools::d(["RESEEEEE" => $arMsOrderDataUpdateAttr, '$UPDA' => $UPDA]);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getBitrixOrder()
    {

        \Cmodule::IncludeModule('sale');

        if ($order = \Bitrix\Sale\Order::load($this->id)) {
            $this->arOrder['FIELDS'] = $order->getFieldValues();

            foreach ($order->getBasket()->getBasketItems() as $item) {
                $this->arOrder['BASKET'][] = [
                    'BITRIX' => $item->getFieldValues(),
                    'MS'     => \Ipol\MS\ProductList::ProductFactory($item->getFieldValues()['PRODUCT_ID'], true)
                ];
            }


            if (count($this->arOrder['BASKET']) > 100) {
                return false; //больше 100 товаров МС не умеет
            }

            foreach ($order->getShipmentCollection() as $shipment) {
                if (!$shipment->isSystem()) {
                    $this->arOrder['SHIPMENT'][] = new Shipment($shipment);
                }
            }

            foreach ($order->getPaymentCollection() as $payment) {
                $this->arOrder['PAYMENT'][] = new Payment($payment);
            }

            return true;
        } else {
            throw new \Exception('Заказ не найден ' . $this->id, 200);
        }
    }


    public static $arOrdersCache = [];

    /**
     *
     */
    public function loadCache()
    {
        self::$arOrdersCache = [];

        $obReq = new \Ipol\MS\VarData();
        $page  = $obReq->makeReq(
            '/entity/customerorder/',
            ['limit' => 1000, 'updatedFrom' => date('Y-m-d H:i:s', time() - 86400 * 7)]
        );

        if ($page) {
            do {
                if (count($page['rows']) == 0) {
                    break;
                }
                $done++;
                foreach ($page['rows'] as $_stockRow) {
                    self::$arOrdersCache[$_stockRow['externalCode']] = $_stockRow;
                }
            } while ($page['meta']['nextHref'] && $page = $obReq->makeReq($page['meta']['nextHref']));
        }

        Tools::g('CACHE FILLED', count(self::$arOrdersCache));
    }

    /**
     * @return bool
     */
    public function getMSOrder()
    {

        if (USE_CACHE_PRECACHE === true && count(self::$arOrdersCache) == 0) {
            $this->loadCache();
        }

        if ($this->mid && ($this->arCustomerOrder = $this->getByMID($this->mid))) {
        } else {
            if (
                array_key_exists($this->id, self::$arOrdersCache) &&
                ($this->arCustomerOrder = self::$arOrdersCache[$this->id])
            ) {
                Tools::g('CACHE FOUND', $this->id);
                $this->mid = $this->arCustomerOrder['id'];
            } elseif (
                ($this->arCustomerOrder = $this->getByFilter(['externalCode=' => $this->id], 1, $this->id)['rows'][0])
                && ($this->arCustomerOrder['externalCode'] == $this->id)
            ) {
                $this->mid = $this->arCustomerOrder['id'];
            }
        }

        if ($this->mid) {
            \Ipol\Core\Tools::setCache('ORD_' . $this->id, $this->mid);
            return true;
        }

        return false;
    }
}
