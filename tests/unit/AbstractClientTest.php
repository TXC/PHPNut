<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\CoversClass;
use TXC\NUT\AbstractClient;
use TXC\NUT\AbstractHandler;
use TXC\NUT\Client;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Tests\ClientTestCase;
use TXC\NUT\Tests\MockServer;

#[CoversClass(AbstractClient::class)]
class AbstractClientTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($this->getValidHandler(true));
    }

    //region Client
    public function testClientConnect(): void
    {
        $client = new Client(handler: $this->getValidHandler(true));
        $this->assertInstanceOf(AbstractClient::class, $client);
        $this->assertInstanceOf(AbstractHandler::class, $client->getHandler());
    }

    public function testClientConnectWithoutHandler(): void
    {
        $client = new Client(debug: false, connect: false);

        $this->expectException(NutException::class);
        $client->connect();
    }

    public function testClientConnectBroken(): void
    {
        $this->expectException(NutException::class);
        $client = new Client(
            handler: $this->getBrokenHandler(),
            debug: true,
            connect: false,
        );
        $client->username(self::VALID);
        $client->password(self::VALID);
    }

    public function testClientConstructWithArguments(): void
    {
        $client = new Client(
            handler: $this->getValidHandler(true),
            debug: true,
            connect: false,
        );
        $client->username(self::VALID);
        $client->password(self::VALID);

        $this->assertInstanceOf(AbstractClient::class, $client);
        $this->assertInstanceOf(AbstractHandler::class, $client->getHandler());
    }

    public function testConnectionFailed(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TXC\NUT\AbstractHandler */
        $handler = $this->getMockBuilder('\TXC\NUT\Tests\MockServer')
            ->setConstructorArgs(['', 0, 0, false])
            ->onlyMethods(['read'])
            ->getMock();

        $this->client = new Client(
            handler: $handler,
            debug: false,
            connect: false
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->client->connect();
    }

    public function testClientSetHandler(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TXC\NUT\AbstractHandler */
        $handler = $this->getMockBuilder('\TXC\NUT\Tests\MockServer')->getMock();
        $handler->write(self::VALID_DESC);
        $handler->method('read')
                ->willReturn([self::VALID_DESC]);

        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($handler);

        $result = $this->client->getHandler()->read();
        $result = current($result);
        $this->assertEquals(self::VALID_DESC, $result);
    }

    public function testClientSetHandlerFailed(): void
    {
        $this->client = new Client(debug: false, connect: false);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TXC\NUT\AbstractHandler */
        $handler1 = $this->getMockBuilder('\TXC\NUT\Tests\MockServer')->getMock();
        $handler1->write(self::VALID_DESC);
        $handler1->method('read')
                ->willReturn([self::VALID_DESC]);
        $this->client->setHandler($handler1);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TXC\NUT\AbstractHandler */
        $handler2 = $this->getMockBuilder('\TXC\NUT\Tests\MockServer')->getMock();
        $handler2->write(self::VALID);
        $handler2->method('read')
                ->willReturn([self::VALID]);

        $this->client->setHandler($handler2);

        $result = $this->client->getHandler()->read();
        $result = current($result);
        $this->assertNotEquals(self::VALID_DESC, $result);
    }
    //endregion Client
}
