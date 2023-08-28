<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception\AccessDenied;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\UnknownUps;
use TXC\NUT\Exception\VarNotSupported;
use TXC\NUT\Exception\InvalidValue;
use TXC\NUT\Exception\InvalidArgument;
use TXC\NUT\Tests\ClientTestCase;

class SetTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);
    }

    //region SET VAR
    public function testSetRwVarsValid(): void
    {
        $result = $this->client->setVar(self::VALID, self::VALID, self::VALID);
        $this->assertStringStartsWith('OK', $result);
        $this->assertStringNotContainsString('TRACKING', $result);
    }

    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testSetRwVarsValidWithTrackingInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT | $handler::TRACKING_ENABLED);
        $this->client->setHandler($handler);

        $result = $this->client->setVar(self::VALID, self::VALID, self::VALID);
        $this->assertStringStartsWith('OK', $result);
        $this->assertStringNotContainsString('TRACKING', $result);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetRwVarsValidWithTracking(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT | $handler::TRACKING_ENABLED);
        $this->client->setHandler($handler);

        $this->client->setTracking(true);
        $result = $this->client->setVar(self::VALID, self::VALID, self::VALID);

        $this->assertStringStartsWith('OK TRACKING', $result);

        $var = explode(' ', $result);
        $this->assertCount(3, $var);
        $this->assertEquals('OK', $var[0]);
        $this->assertEquals('TRACKING', $var[1]);
        $this->assertUuid($var[2]);
    }

    public function testSetRwVarsInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setVar(self::INVALID, self::VALID, self::VALID);
    }

    public function testSetRwVarsInvalidVariable(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->setVar(self::VALID, self::INVALID, self::VALID);
    }

    public function testSetRwVarsInvalidInvalidValue(): void
    {
        $this->expectException(InvalidValue::class);
        $this->client->setVar(self::VALID, self::VALID, self::INVALID);
    }

    public function testSetRwVarsBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->setVar(self::VALID, self::VALID, self::VALID);
    }
    //endregion SET VAR

    //region SET TRACKING
    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testSetTrackingInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->setTracking(true);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingActivateNotLoggedIn(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(AccessDenied::class);
        $this->client->setTracking(true);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingInactivateNotLoggedIn(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(AccessDenied::class);
        $this->client->setTracking(false);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingActivate(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $res = $this->client->setTracking(true);
        $this->assertEquals('OK', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingInactivate(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $res = $this->client->setTracking(false);
        $this->assertEquals('OK', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingActivateBroken(string $serverVersion): void
    {
        $handler = $this->getBrokenHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $this->client->setTracking(true);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testSetTrackingInactivateBroken(string $serverVersion): void
    {
        $handler = $this->getBrokenHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $this->client->setTracking(false);
    }
    //setregion SET TRACKING
}
