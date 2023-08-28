<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception\AccessDenied;
use TXC\NUT\Exception\CmdNotSupported;
use TXC\NUT\Exception\InvalidArgument;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\UnknownUps;
use TXC\NUT\Tests\ClientTestCase;
use TXC\NUT\Tests\MockServer;

class MiscTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($this->getValidHandler(true));
    }

    //region HELP
    public function testHelp(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->help();
        $expected = 'Commands: HELP VER GET LIST SET INSTCMD LOGIN LOGOUT ' .
                    'USERNAME PASSWORD STARTTLS';
        $this->assertEquals($expected, $response);
    }
    //endregion HELP

    //region VER
    public function testVer(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->ver();
        $expected = sprintf($this->serverInfo, $this->serverVersion);
        $this->assertEquals($expected, $response);
    }

    public function testVersion(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->version();
        $this->assertEquals($this->serverVersion, $response);
    }

    public function testVersionInvalidVersionString(): void
    {
        $serverVersionValid = '2.8.3';
        $serverVersionInvalid = $serverVersionValid . '#"!';

        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersionValid);
        $this->client->setHandler($handler);

        $response = $this->client->version();
        $this->assertNotEquals($serverVersionInvalid, $response);
        $this->assertEquals($serverVersionValid, $response);
    }

    public function testVersionInvalidResponse(): void
    {
        $serverVersion = 'abc%&/()';

        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $response = $this->client->version();
        $this->assertNotEquals($serverVersion, $response);
    }

    public function testVersionBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $response = $this->client->version();
        $this->assertEquals($this->serverVersion, $response);
    }
    //endregion VER

    //region NETVER/PROTVER
    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    public function testNetVerInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->netver();
    }

    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testProtVerInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->protver();
    }

    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testNetVer(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::TRACKING_ENABLED);
        $handler->setExpectedValue('SUCCESS');
        $this->client->setHandler($handler);

        $response = $this->client->netver();
        $this->assertEquals($this->getProtocolVersion($serverVersion), $response);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testProtVer(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::TRACKING_ENABLED);
        $handler->setExpectedValue('SUCCESS');
        $this->client->setHandler($handler);

        $response = $this->client->protver();
        $this->assertEquals($this->getProtocolVersion($serverVersion), $response);
    }

    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testNetVerBroken(string $serverVersion): void
    {
        $handler = $this->getBrokenHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);

        $this->client->netver();
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testProtVerBroken(string $serverVersion): void
    {
        $handler = $this->getBrokenHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $this->client->protver();
    }
    //endregion NETVER/PROTVER

    //region INSTCMD
    public function testInstCmdNotLoggedIn(): void
    {
        $this->client->setHandler($this->getValidHandler());

        $this->expectException(AccessDenied::class);
        $this->client->instcmd(self::VALID, self::VALID);
    }

    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testInstCmdWithParameterInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->instcmd(self::VALID, self::VALID, self::VALID);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testInstCmdWithParameter(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        //$this->expectException(CmdNotSupported::class);
        $res = $this->client->instcmd(self::VALID, self::VALID, self::VALID);
        $this->assertStringStartsWith('OK', $res);
    }

    public function testInstCmdInvalidUpsName(): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(UnknownUps::class);
        $this->client->instcmd(self::INVALID, self::VALID);
    }

    public function testInstCmdInvalidCommand(): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(CmdNotSupported::class);
        $this->client->instcmd(self::VALID, self::INVALID);
    }

    public function testInstCmdBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->instcmd(self::VALID, self::VALID);
    }
    //endregion INSTCMD

    //region FSD
    public function testFsdNotLoggedIn(): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags(0);
        $this->client->setHandler($handler);

        $this->expectException(AccessDenied::class);
        $this->client->fsd(self::VALID);
    }

    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testFsdLoggedInValidUps(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $result = $this->client->fsd(self::VALID);
        $this->assertEquals('OK FSD-SET', $result);
    }

    public function testFsdLoggedInInvalidUps(): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(UnknownUps::class);
        $this->client->fsd(self::INVALID);
    }

    public function testFsdBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->fsd(self::VALID);
    }

    public function testFsdNotOk(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getNotOkHandler());
        $this->client->fsd(self::VALID);
    }
    //endregion FSD
}
