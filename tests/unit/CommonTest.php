<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception;
use TXC\NUT\Telnet\Command;
use TXC\NUT\Tests\ClientTestCase;
use TXC\NUT\Telnet\DoublerTrait;

class CommonTest extends ClientTestCase
{
    use DoublerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($this->getValidHandler(true));
    }

    public function testSendCommandNoParam(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client->sendCommand();
    }

    #[TestWith(['ALREADY-SSL-MODE', Exception\AlreadySSLMode::class])]
    #[TestWith(['DATA-STALE', Exception\DataStale::class])]
    #[TestWith(['DRIVER-NOT-CONNECTED', Exception\DriverNotConnected::class])]
    #[TestWith(['FEATURE-NOT-SUPPORTED', Exception\FeatureNotSupported::class])]
    #[TestWith(['INSTCMD-FAILED', Exception\InstcmdFailed::class])]
    #[TestWith(['PASSWORD-REQUIRED', Exception\PasswordRequired::class])]
    #[TestWith(['READONLY', Exception\VarIsReadOnly::class])]
    #[TestWith(['SET-FAILED', Exception\SetFailed::class])]
    #[TestWith(['TOO-LONG', Exception\TooLong::class])]
    #[TestWith(['UNKNOWN-COMMAND', Exception\UnknownCommand::class])]
    #[TestWith(['USERNAME-REQUIRED', Exception\UsernameRequired::class])]
    public function testSendCommandWithErrorCode(string $errorCode, string $exception): void
    {
        $handler = $this->getBrokenHandler(true);
        $handler->setErrorCode($errorCode);
        $this->client->setHandler($handler);

        $this->expectException($exception);
        $this->client->sendCommand(self::VALID);
    }

    public function testGetOperationResponseIsMultiLine(): void
    {
        $handler = $this->getMockHandler(true);

        $handler->method('read')
                ->willReturn(['OK', '']);

        $this->client->setHandler($handler);

        $result = $this->client->sendCommand('GET', 'VER');

        $this->assertCount(2, $result);
    }

    public function testGetOperationInvalidResponse(): void
    {
        $handler = $this->getMockHandler(true);

        $handler->method('read')
                ->willReturn(['LIST']);

        $this->client->setHandler($handler);

        $this->expectException(Exception\NutException::class);
        $this->client->getNumLogins(self::VALID);
    }

    public function testListOperationInvalidResponseBeginLine(): void
    {
        $handler = $this->getMockHandler(true);

        $handler->method('read')
                ->willReturn([
                    'LIST UPS',
                    'UPS ' . self::VALID,
                    'END LIST UPS'
                ]);

        $this->client->setHandler($handler);

        $this->expectException(Exception\NutException::class);
        $this->client->listUPS();
    }

    public function testListOperationInvalidResponseLastLine(): void
    {
        $handler = $this->getMockHandler(true);

        $handler->method('read')
                ->willReturn([
                    'BEGIN LIST UPS',
                    'UPS ' . self::VALID,
                    'LIST UPS'
                ]);

        $this->client->setHandler($handler);

        $this->expectException(Exception\NutException::class);
        $this->client->listUPS();
    }


    public function testIACBuffer(): void
    {
        $words = $this->faker->words();
        $words[] = Command::IAC->chr();
        shuffle($words);

        $actual = $this->doubleCharacter(implode(' ', $words), Command::IAC);
        $this->assertStringContainsString(
            Command::IAC->chr() . Command::IAC->chr(),
            $actual
        );
    }
}
