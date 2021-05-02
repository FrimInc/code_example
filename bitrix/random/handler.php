<?php

namespace IPOL\Api;

function ErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
{
	if ($errno == E_ERROR) {

		while (ob_get_level()) {
			ob_end_clean();
		}

		$obHandler = new Handler();
		$obHandler->LogEx(new \Exception(print_r(func_get_args(), true), $errno));
		$error_message = $obHandler::SHOW_INTERNAL_EXCEPTIONS ? ($errstr . ':' . $errfile . ':' . $errline) : 'Internal error';
		$error_code = 500;
		$obHandler->DropError($error_message, $error_code);

	} else if ($errno == E_WARNING) {
		$obHandler = new Handler();
		$obHandler->LogEx(new \Exception(print_r(func_get_args(), true), $errno));
	}

}

function FatalHandler($DisplayErrors = false)
{
	$error = error_get_last();

	if ($error['type'] == E_ERROR || $error['type'] == E_WARNING) {
		ErrorHandler(E_ERROR, $error['message'], $error['file'], $error['line'], $context = null);
		exit();
	}

}

Class Handler
{

	use \IPOL\Tools\JsonFunctions;
	use \IPOL\Tools\Debug;

	const SHOW_INTERNAL_EXCEPTIONS = true;
	const SERVICE_DIR = '/api/';

	var $langs = array(
		'ru' => 'Русский',
		'en' => 'English',
		'bg' => 'Болгарский',
		'gr' => 'Греческий',
	);

	var $serviceFields = array(
		'sms_timer' => 0
	);

	var $strings = array();

	private function MakeReturn($data = null)
	{
		header('Content-type: application/json;');
		if (!is_array($data) || !array_key_exists('result', $data)) {

			$arReturn = array('result' => $data, 'error_code' => 0, 'error_message' => '');

		} else {
			$arReturn = array('result' => $data['result'], 'error_code' => $data['error_code'], 'error_message' => $data['error_message']);
		}

		if ($this->serviceFields['sms_timer'] > 0) {
			$arReturn['sms_timer'] = $this->serviceFields['sms_timer'];
		}

		array_walk_recursive($arReturn, function (&$val, $key) {
			if ($val === null) {
				$val = '';
			} else if (is_scalar($val)) {
				$val = (string)$val;
			}
		});

		$arReturn = $this->try_json_encode($arReturn);

		return $arReturn;
	}

	public function CheckLang($strString)
	{
		$strString = str_replace(array_keys($this->strings), array_values($this->strings), $strString);

		return $strString;
	}

	function DropError($errorMessage = '', $errorCode = 0)
	{

		$errorMessage = $this->CheckLang($errorMessage);

		echo $this->MakeReturn(array('result' => null, 'error_message' => $errorMessage, 'error_code' => $errorCode));
		die();
	}

	private function GetMethodName()
	{
		return array_shift($a = explode('?', strtolower(str_ireplace(array(self::SERVICE_DIR, '/'), '', $_SERVER['REQUEST_URI']))));
	}

	private function GetInputParams()
	{

		if (is_array($_GET) && count($_GET) != 0) {
			$arParams = $_GET;
		} else {
			$arParams = file_get_contents('php://input');

			global $DATAin;
			$DATAin = $arParams;

			if (!$this->try_json_decode($arParams, $error)) {
				$this->DropError($error, 100);
			}
		}

		return $arParams;
	}

	public function Handle()
	{

		set_error_handler('IPOL\Api\ErrorHandler');
		register_shutdown_function('IPOL\Api\FatalHandler', self::SHOW_INTERNAL_EXCEPTIONS);
		error_reporting(0);

		$Return = '';
		$obServer = new Server();
		$lang = false;

		foreach (getallheaders() as $name => $value) {
			$name = strtolower($name);
			if ($name == 'lang') {
				$lang = substr(trim((string)$value), 0, 2);
			} else if ($name == 'tcp') {
				$obServer->TCP = true;
			}
		}

		$lang = $this->langs[$lang] ? $lang : 'en';

		if (file_exists($langPath = __DIR__ . '/../loc/' . $lang . '/lang.php')) {
			include($langPath);
			$this->strings = $_MESS;
		}

		$method = $this->GetMethodName();

		if (!method_exists($obServer, $method)) {
			$this->DropError('METHOD_NOT_FOUND: ' . $method, 5);
		} else if (($arParams = $this->GetInputParams()) || $method == 'help') {

			try {
				if ($method == 'help') {
					if (!($Return = $obServer->$method($arParams, $this))) {
						$this->DropError($obServer->LAST_ERROR, $obServer->ERROR_CODE);
					}
				} else {
					if (!($Return = $obServer->$method($arParams))) {
						$this->DropError($obServer->LAST_ERROR, $obServer->ERROR_CODE);
					}
				}

			} catch (\Exception $e) {
				$this->LogEx($e);

				if (self::SHOW_INTERNAL_EXCEPTIONS) {
					$this->DropError($e->getMessage(), $e->getCode());
				}

				if (($erCode = $e->getCode()) > 200) {
					$er_mess = 'INNER_EXCEPTION';
				} else {
					$er_mess = $e->getMessage();
				}

				$this->DropError($er_mess, $e->getCode());
			}

		}

		echo $this->MakeReturn($Return);

		die();
	}

}
