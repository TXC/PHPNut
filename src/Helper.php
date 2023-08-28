<?php

namespace TXC\NUT;

use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Level;
use Monolog\Logger;

class Helper
{
    private static ?Logger $logger = null;

    public static function log(?string $withName = null): Logger
    {
        if (!(self::$logger instanceof Logger)) {
             throw new \BadMethodCallException('No instance found');
             //self::$logger = self::getLogger('default');
        }
        if (!empty($withName)) {
            $orgName = self::$logger->getName();
            return self::$logger->withName($orgName . '.' . $withName);
        }
        return self::$logger;
    }

    public static function getLogger(string $name, bool $debug = false): Logger
    {
        if (self::$logger instanceof Logger) {
            if (self::$logger->getName() == $name) {
                return self::$logger;
            }
            return self::$logger->withName($name);
        }

        $logger = new Logger($name);
        $logger->pushProcessor(new PsrLogMessageProcessor());
        //$logger->pushProcessor(new UidProcessor());
        $logger->pushHandler(new StreamHandler(stream: 'php://stderr', level: Level::Error));
        if ($debug) {
            $logger->pushHandler(new StreamHandler(stream: 'php://stdout', level: Level::Debug, bubble: false));
        } else {
            $logger->pushHandler(new StreamHandler(stream: 'php://stdout', level: Level::Info, bubble: false));
        }
        return self::$logger = $logger;
    }
}
