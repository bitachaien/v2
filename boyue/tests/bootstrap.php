<?php



require_once __DIR__ . '/../vendor/autoload.php';


if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}


define('BASE_PATH', dirname(__DIR__));
define('TESTING', true);
