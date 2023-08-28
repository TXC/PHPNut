<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\CoversClass;
use TXC\NUT\Client;
use TXC\NUT\AbstractClient;
use TXC\NUT\AbstractHandler;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Tests\ClientTestCase;
use TXC\NUT\Tests\MockServer;

#[CoversClass(AbstractHandler::class)]
class AbstractHandlerTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        //$this->client = new Client(debug: false, connect: false);
        //$this->client->setHandler($this->getValidHandler(true));
    }

    //region Handler
    public function testHandlerGetSocket(): void
    {
        $handler = $this->getValidHandler(true);
        $this->assertNotNull($handler->getSocket());
    }

    public function testHandlerGetSocketExisting(): void
    {
        $handler = $this->getValidHandler(true);
        $actual = $handler->getSocket();

        $handler->open();
        $expected = $handler->getSocket();
        $this->assertSame($actual, $expected);
    }

    public function testHandlerGetSocketFail(): void
    {
        $handler = new MockServer(host: '', port: 0, secure: false);
        $this->assertNull($handler->getSocket());
    }

    public function testHandlerOpenFail(): void
    {
        $handler = new MockServer(host: '', port: 0, secure: false);
        $this->expectException(\InvalidArgumentException::class);
        $handler->open();
    }
    //endregion Handler
}
