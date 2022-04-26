<?php

namespace App\Helpers;

use Exception;
use Psr\Log\LoggerInterface;

class Logger
{

    private static LoggerInterface $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    /**
     * @param $w
     * @return void
     */
    public static function log($w)
    {
        echo '<pre>' . print_r([__FILE__, __LINE__, date('d.m.Y H:i:s'), $w], true) . '</pre>';
    }

    /**
     * @param array $record
     * @return array
     */
    public function processRecord(array $record)
    {
        if (!array_key_exists('REQUEST_ID', $_SERVER)) {
            $_SERVER['REQUEST_ID'] = 'inner ' . $_SERVER['REQUEST_TIME'] . ' ' . implode(' ', $_SERVER['argv']);
        }
        $record['extra']['request_id'] = $_SERVER['REQUEST_ID'];

        return $record;
    }

    /**
     * @param string $strMessage
     * @return void
     */
    public static function logError(string $strMessage)
    {
        self::$logger->error($strMessage);
    }

    /**
     * @param Exception $eException
     * @return void
     */
    public static function logException(Exception $eException)
    {
        self::$logger->error(
            $eException->getMessage(),
            [
                'exception' => $eException
            ]
        );
    }

    /**
     * @param string $strMessage
     * @return void
     */
    public static function logInfo(string $strMessage)
    {
        self::$logger->info($strMessage);
    }
}
