<?php

use TXC\NUT\AbstractClient;
use TXC\NUT\Client;
use TXC\NUT\Helper;
use TXC\NUT\Tests\MockServer;
use TXC\NUT\Tests\ClientTestCase;
use TXC\NUT\Exception\NutException;

function showResults(AbstractClient $client, string $method, ...$args): void
{
    echo str_repeat('-', 80) . PHP_EOL;
    printf('Testing \'%s\' :', $method);
    try {
        $result = $client->{$method}(...$args);
        if (!empty($result)) {
            $possibleUpsName = current($args);
            if (
                is_array($result)
                && isset($result[$possibleUpsName])
                && is_array($result[$possibleUpsName])
            ) {
                $result = $result[$possibleUpsName];
            }
            if (
                isset($result)
                && is_array($result)
                && count($result) > 0
            ) {
                foreach ($result as $res) {
                    printf("\033[01;33m%s\033[0m\n", $res);
                }
            } else {
                printf("\033[01;33m%s\033[0m\n", $result);
            }
        } else {
            printf("\033[01;33mVoid Result\033[0m\n");
        }
    } catch (NutException $e) {
        printf("\033[01;30mCaught Exception: \033[01;31m%s\033[0m\n", $e->getMessage());
    }
}

$cur = dirname(__FILE__);
require_once $cur . '/vendor/autoload.php';

$logger = Helper::getLogger('nut', false);

$handler = new MockServer(host: '127.0.0.1', port: 3493, secure: false);
$handler->setExpectedValue(ClientTestCase::VALID);
$handler->setExpectedDesc(ClientTestCase::VALID_DESC);
$client = new Client(handler: $handler, debug: true, connect: false);

$client->username(ClientTestCase::VALID);
$client->password(ClientTestCase::VALID);

$client->setTracking(true);

showResults($client, 'listUPS');
showResults($client, 'listClients', ClientTestCase::VALID);
showResults($client, 'listEnum', ClientTestCase::VALID, ClientTestCase::VALID);
showResults($client, 'listRange', ClientTestCase::VALID, ClientTestCase::VALID);
showResults($client, 'listVars', ClientTestCase::VALID);
showResults($client, 'listCommands', ClientTestCase::VALID);
showResults($client, 'listRWVars', ClientTestCase::VALID);

showResults($client, 'getVar', ClientTestCase::VALID, ClientTestCase::VALID);
showResults($client, 'getTracking');
showResults($client, 'getTracking', ClientTestCase::VALID);
showResults($client, 'varDescription', ClientTestCase::VALID, ClientTestCase::VALID);
showResults($client, 'varType', ClientTestCase::VALID, ClientTestCase::VALID);

showResults($client, 'runCommand', ClientTestCase::VALID, ClientTestCase::VALID);
showResults($client, 'setVar', ClientTestCase::VALID, ClientTestCase::VALID, ClientTestCase::VALID);
