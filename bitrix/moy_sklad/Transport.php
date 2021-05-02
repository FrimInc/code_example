<?php

namespace Ipol\MS;

use Complex\Exception;

class Transport
{

    public static $lastReq = 0;

    public $cachable = false;

    public const MS_URL_BASE = 'https://online.moysklad.ru/api/remap/1.1/';
    public const MS_URL      = 'https://online.moysklad.ru/api/remap/1.1/entity/';

    public $strEntityType = '';

    public $mid = '';

    public static $meta     = null;
    public static $arGroups = false;


    /**
     * Transport constructor.
     */
    public function __construct()
    {
        if (!is_array(self::$arGroups)) {
            self::$arGroups = [];
            $obReq          = new \Ipol\MS\VarData();
            if ($page = $obReq->makeReq('/entity/group/')) {
                do {
                    $done++;
                    foreach ($page['rows'] as $arGroupRow) {
                        self::$arGroups[$arGroupRow['name']] = $arGroupRow['meta'];
                    }
                } while ($page['meta']['nextHref'] && $page = $obReq->makeReq($page['meta']['nextHref']));
            }
        }
    }

    /**
     * @param $arData
     * @return bool|mixed
     * @throws \Exception
     */
    protected function putItem($arData)
    {
        return $this->ms_query(self::MS_URL . $this->strEntityType, $arData, 'POST');
    }

    /**
     * @param       $arData
     * @param false $cacheSid
     * @return bool|mixed|string
     * @throws \Exception
     */
    protected function updateItem($arData, $cacheSid = false)
    {

        if ($cacheSid && \Ipol\Core\Tools::checkCache($this->strEntityType . '/' . $this->mid, $arData, $cacheSid)) {
            return 'CACHED';
        }

        try {
            $arRes = $this->ms_query(self::MS_URL . $this->strEntityType . '/' . $this->mid, $arData, "PUT");
        } catch (\Exception $e) {
            \Ipol\Core\Tools::deleteCache($this->strEntityType . '/' . $this->mid, $cacheSid);
            throw $e;
        }

        return $arRes;
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    protected function deleteItem()
    {
        return $this->ms_query(self::MS_URL . $this->strEntityType . '/' . $this->mid, [], "DELETE");
    }

    /**
     * @param array $arFilter
     * @return string
     */
    protected function createFilter($arFilter = [])
    {
        $strFilter = [];

        foreach ($arFilter as $field => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            foreach ($value as $_val) {
                $strFilter[] = $field . $_val;
            }
        }

        $strFilter = implode(';', $strFilter);

        return $strFilter;
    }

    /**
     * @param array  $arFilter
     * @param int    $intLimit
     * @param string $search
     * @return bool|mixed
     * @throws \Exception
     */
    protected function getByFilter($arFilter = [], $intLimit = 25, $search = '')
    {
        return $this->ms_query(self::MS_URL . $this->strEntityType, array_filter([
            'search' => $search,
            'filter' => $this->CreateFilter($arFilter),
            'limit'  => $intLimit < 0 ? '' : (intval($intLimit) ?: 25)
        ]));
    }

    /**
     * @param string $mid
     * @return bool|mixed
     * @throws \Exception
     */
    protected function getByMID($mid = '')
    {
        \Ipol\MS\Tools::ValidateString($mid);

        return $this->ms_query(self::MS_URL . $this->strEntityType . '/' . $mid, '');
    }

    /**
     * @param        $link
     * @param array  $data
     * @param string $request
     * @param int    $iterations
     * @return bool|mixed
     * @throws \Exception
     */
    protected function ms_query($link, $data = [], $request = "GET", $iterations = 0)
    {
        if ($this->cachable && $request == 'GET') {
            $_key = md5(serialize([$link, $data]));
            if ($R = \Ipol\Core\Tools::getCache($_key)) {
                Tools::g($link, 'FROM CACHE');
                return $R;
            }
        }

        global $ARGSSS;
        $_initial_link    = $link;
        $_initial_data    = $data;
        $_initial_request = $request;
        $iterations++;

        while (!defined('SKIP_MS_DELAY') && microtime(1) < self::$lastReq) {
            continue;
        }

        self::$lastReq = microtime(1) + 0.07;
        $curl          = curl_init();

        if ($data && $request != 'GET' && $request != 'DELETE') {
            $send_data = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data);

            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Length: ' . strlen($send_data)]);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } elseif ($data && $request != 'DELETE') {
            $link = $link . '?' . http_build_query($data);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'IpolApi-client/1.0');

        curl_setopt($curl, CURLOPT_URL, $link);

        if ($request != 'GET') {
            if ($request == 'POST') {
                curl_setopt($curl, CURLOPT_POST, $request == 'POST');
            }

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);
        }


        curl_setopt($curl, CURLOPT_USERPWD, Constants::AUTH_DATA);


        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        if (in_array('TIMES', $ARGSSS) || $_REQUEST['ORDER_ID']) {
            $__start = microtime(1);
        }
        $out = curl_exec($curl);
        if (($took = (microtime(1) - $__start)) > 0.5) {
            if (in_array('TIMES', $ARGSSS) || $_REQUEST['ORDER_ID']) {
                Tools::g("TIME QUERY " . $took . ' - ' . $request . ' ' . $link);
            }
        }


        $json = json_decode($out, JSON_UNESCAPED_UNICODE);
        // Tools::g($data,$json?:$out );

        if ($request == 'DELETE') {
            curl_close($curl);

            if (200 == curl_getinfo($curl, CURLINFO_HTTP_CODE) || !$json) {
                return true;
            } elseif ($json) {
                return $json;
            }
        }

        if (!$json) {
            $info       = curl_getinfo($curl);
            $cerrorno   = curl_errno($curl);
            $cerrorinfo = curl_error($curl);
            Tools::t($link, $info, $cerrorno, $cerrorinfo, $out);
            if ($iterations < 10) {
                self::$lastReq = microtime(1) + 2.5;
                while (microtime(1) < self::$lastReq) {
                    continue;
                }
                curl_close($curl);

                return $this->ms_query($_initial_link, $_initial_data, $_initial_request, $iterations);
            }
            Tools::t($link, $info, $cerrorno, $cerrorinfo, $out);
            curl_close($curl);

            throw new \Exception(print_r($cerrorinfo, true));
            return false;
        }
        curl_close($curl);

        if ($json['errors']) {
            if ($json['errors'][0]['code'] == 1049 && $iterations < 10) {
                self::$lastReq = microtime(1) + 2.5;
                while (microtime(1) < self::$lastReq) {
                    continue;
                }

                return $this->ms_query($_initial_link, $_initial_data, $_initial_request, $iterations);
            }
        }

        if ($this->cachable && $request == 'GET' && ($json['id'] || is_array($json['rows']) && count($json['rows']))) {
            \Ipol\Core\Tools::setCache($_key, $json);
        }

        return $json;
    }

    /**
     * @param       $entity
     * @param       $filter
     * @param false $dataFilter
     * @return false|mixed
     * @throws \Exception
     */
    public function getRandomProperty($entity, $filter, $dataFilter = false)
    {
        $link = 'https://online.moysklad.ru/api/remap/1.1/entity/' . $entity;
        $json = $this->ms_query($link, $D = array_filter(['filter' => $this->CreateFilter($filter)]));

        if (is_array($dataFilter)) {
            foreach ($json['rows'] as $_row) {
                foreach ($dataFilter as $_k => $v) {
                    if ($_row[$_k] == $v) {
                        $ret = $_row;
                        break 2;
                    }
                }
            }
        } else {
            $ret = $json['rows'][0];
        }

        return $ret ?: false;
    }
}
