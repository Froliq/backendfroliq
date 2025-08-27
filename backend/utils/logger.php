<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../../vendor/autoload.php';

class AppLogger {
    private static $logger;

    public static function getLogger() {
        if (!self::$logger) {
            self::$logger = new Logger('app');
            self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
            self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/error.log', Logger::ERROR));
        }
        return self::$logger;
    }
}
