<?php

declare(strict_types=1);

namespace TXC\NUT\Tests;

use PHPUnit\Framework\TestCase;
use TXC\NUT\Client;
use Faker\Factory as Faker;
use Faker\Generator;
use TXC\NUT\Exception\NutException;
use TXC\NUT\ProtocolVersionTrait;

abstract class ClientTestCase extends TestCase
{
    use ProtocolVersionTrait;

    protected Client $client;
    protected Generator $faker;
    public const VALID = 'test';
    public const INVALID = 'does_not_exist';
    public const VALID_UPS_NAME = 'Test UPS 1';
    public const VALID_DESC = 'Test UPS 1';
    public const VALID_VALUE = '100';
    public const VALID_CMD_DESC = 'Test UPS 1';
    protected string $serverInfo = 'Network UPS Tools upsd %s - http://www.networkupstools.org/';
    /**
     * Protocol version
     * | Prot.ver | NUT Ver. |
     * -----------------------
     * | 1.0      | < 1.5    |
     * | 1.1      | >= 1.5   |
     * | 1.2      | >= 2.6.4 |
     * | 1.3      | >= 2.8.0 |
     */
    protected string $serverVersion = '2.7.4';

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);

        /*
        self.client = PyNUTClient(connect=False, debug=True)
        self.client._srv_handler = MockServer(broken=False)
        self.broken_client = PyNUTClient(connect=False, debug=True)
        self.broken_client._srv_handler = MockServer(broken=True)
        self.not_ok_client = PyNUTClient(connect=False, debug=True)
        self.not_ok_client._srv_handler = MockServer(ok=False,
            broken=False)
        telnetlib.Telnet = Mock()
        */
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\TXC\NUT\AbstractHandler
     */
    protected function getMockHandler(bool $fresh = false)
    {
        static $mockHandler;
        if (!empty($mockHandler) && $fresh === false) {
            $handler = $mockHandler;
        } else {
            $handler = $this->getMockBuilder('\TXC\NUT\Tests\MockServer')
                            ->onlyMethods(['read'])
                            ->getMock();
        }

        /*
        $handler->expects($this->once())
            ->method('read')
            ->willReturn(['OK']);
        */

        if (empty($mockHandler)) {
            $mockHandler = $handler;
        }
        return $handler;
    }

    protected function getValidHandler(bool $fresh = false): MockServer
    {
        // IsBroken(false)
        // IsOk(true)
        static $validHandler;
        if (!empty($validHandler) && $fresh === false) {
            $handler = $validHandler;
        } else {
            $handler = new MockServer(host: '127.0.0.1', port: 3493, secure: false);
        }
        $handler->setExpectedValue(self::VALID);
        $handler->setExpectedDesc(self::VALID_DESC);
        $handler->setServerVersion($this->serverVersion);
        if (empty($validHandler)) {
            $validHandler = $handler;
        }
        return $handler;
    }

    protected function getBrokenHandler(bool $fresh = false): MockServer
    {
        // IsBroken(true)
        // IsOk(true)
        static $brokenHandler;
        if (!empty($brokenHandler) && $fresh === false) {
            $handler = $brokenHandler;
        } else {
            $handler = new MockServer(host: '127.0.0.1', port: 3493, secure: false);
        }
        $handler->setExpectedValue(self::VALID);
        $handler->setExpectedDesc(self::VALID_DESC);
        $handler->setIsBroken(true);
        $handler->setServerVersion($this->serverVersion);
        if (empty($brokenHandler)) {
            $brokenHandler = $handler;
        }
        return $handler;
    }

    protected function getNotOkHandler(bool $fresh = false): MockServer
    {
        // IsBroken(false)
        // IsOk(false)
        static $notOkHandler;
        if (!empty($notOkHandler) && $fresh === false) {
            $handler = $notOkHandler;
        } else {
            $handler = new MockServer(host: '127.0.0.1', port: 3493, secure: false);
        }
        $handler->setExpectedValue(self::VALID);
        $handler->setExpectedDesc(self::VALID_DESC);
        $handler->setIsOk(false);
        $handler->setServerVersion($this->serverVersion);
        if (empty($notOkHandler)) {
            $notOkHandler = $handler;
        }
        return $handler;
    }

    protected function assertUuid(string $uuid, string $message = ''): void
    {
        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $uuid, $message);
    }
}
