<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception\AccessDenied;
use TXC\NUT\Exception\AlreadyLoggedIn;
use TXC\NUT\Exception\AlreadySetUsername;
use TXC\NUT\Exception\AlreadySetPassword;
use TXC\NUT\Exception\InvalidArgument;
use TXC\NUT\Exception\InvalidPassword;
use TXC\NUT\Exception\InvalidUsername;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\UnknownUps;
use TXC\NUT\Tests\ClientTestCase;

class UserTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($this->getValidHandler(true));
        //$this->client->username(self::VALID);
        //$this->client->password(self::VALID);
    }

    //region USERNAME
    #[DoesNotPerformAssertions]
    public function testUsernameValid(): void
    {
        $this->client->username(self::VALID);
    }

    public function testUsernameInvalidUsername(): void
    {
        $this->expectException(InvalidUsername::class);
        $this->client->username(self::INVALID);
    }

    public function testUsernameAlreadySet(): void
    {
        $this->expectException(AlreadySetUsername::class);
        $this->client->username(self::VALID);
        $this->client->username(self::VALID);
    }

    public function testUsernameBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->username(self::VALID);
    }
    //endregion USERNAME

    //region PASSWORD
    #[DoesNotPerformAssertions]
    public function testPasswordValid(): void
    {
        $this->client->password(self::VALID);
    }

    public function testPasswordInvalidPassword(): void
    {
        $this->expectException(InvalidPassword::class);
        $this->client->password(self::INVALID);
    }

    public function testPasswordAlreadySet(): void
    {
        $this->expectException(AlreadySetPassword::class);
        $this->client->password(self::VALID);
        $this->client->password(self::VALID);
    }

    public function testPasswordBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->password(self::VALID);
    }
    //endregion PASSWORD

    //region MASTER
    public function testMasterValid(): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $result = $this->client->master(self::VALID);
        $this->assertStringStartsWith('OK', $result);

        $this->assertStringContainsString('MASTER-GRANTED', $result);
        $this->assertStringNotContainsString('PRIMARY-GRANTED', $result);
    }

    public function testMasterNotLoggedIn(): void
    {
        $this->expectException(AccessDenied::class);
        $this->client->master(self::VALID);
    }

    public function testMasterInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);
        $this->client->master(self::INVALID);
    }

    public function testMasterBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->master(self::VALID);
    }
    //endregion MASTER

    //region PRIMARY
    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testPrimaryInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->primary(self::VALID);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testPrimaryValid(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $result = $this->client->primary(self::VALID);
        $this->assertStringStartsWith('OK', $result);

        $this->assertStringContainsString('PRIMARY-GRANTED', $result);
        $this->assertStringNotContainsString('MASTER-GRANTED', $result);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testPrimaryNotLoggedIn(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(AccessDenied::class);
        $this->client->primary(self::VALID);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testPrimaryInvalidUPS(string $serverVersion): void
    {
        $this->expectException(UnknownUps::class);
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->client->primary(self::INVALID);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testPrimaryBroken(string $serverVersion): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());

        $this->client->primary(self::VALID);
    }
    //endregion PRIMARY

    //region LOGIN
    public function testLoginValid(): void
    {
        $this->client->username(self::VALID);
        $this->client->password(self::VALID);

        $result = $this->client->login(self::VALID);

        $this->assertIsString($result);
        $this->assertEquals('OK', $result);
    }

    public function testLoginNoAuth(): void
    {
        $this->expectException(AccessDenied::class);
        $this->client->login(self::VALID);
    }

    public function testLoginAlreadyLoggedIn(): void
    {
        $this->expectException(AlreadyLoggedIn::class);
        $handler = $this->getValidHandler(true);
        $handler->initialFlags(
            $handler::USERNAME_SENT | $handler::PASSWORD_SENT | $handler::UPS_LOGIN
        );
        $this->client->setHandler($handler);

        $this->client->login(self::VALID);
    }

    public function testLoginInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $handler = $this->getValidHandler(true);
        $handler->initialFlags($handler::USERNAME_SENT | $handler::PASSWORD_SENT);
        $this->client->setHandler($handler);

        $this->client->login(self::INVALID);
    }

    public function testLoginBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->login(self::VALID);
    }
    //endregion LOGIN

    //region LOGOUT
    public function testLogoutValid(): void
    {
        $result = $this->client->logout();
        $this->assertStringStartsWith('OK Goodbye', $result);
    }

    public function testLogoutInvalid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->client->sendCommand('LOGOUT', self::VALID);
    }

    public function testLogoutBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->logout();
    }
    //endregion LOGOUT
}
