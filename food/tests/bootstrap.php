<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    include dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())
        ->load(
            dirname(__DIR__) . '/.env',
            dirname(__DIR__) . '/.env.local',
            dirname(__DIR__) . '/.env.test'
        );
}
