<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use Faker\Factory as Faker;
use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception\FeatureNotConfigured;
use TXC\NUT\Exception\InvalidArgument;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\UnknownUps;
use TXC\NUT\Exception\VarNotSupported;
use TXC\NUT\Tests\ClientTestCase;

class GetTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $handler = $this->getValidHandler(true);
        //$handler->setServerVersion('2.8.0');
        $this->client->setHandler($handler);
    }

    //region NUMLOGINS
    public function testGetNumLogins(): void
    {
        $randomInt = random_int(1, 99);
        $handler = $this->getValidHandler();
        $handler->setExpectedValue(self::VALID);
        $handler->setExpectedDesc((string) $randomInt);
        $this->client->setHandler($handler);

        $response = $this->client->getNumLogins(self::VALID);

        $this->assertEquals($randomInt, $response);
    }

    public function testGetNumLoginsInvalid(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->getNumLogins(self::INVALID);
    }

    public function testGetNumLoginsBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->getNumLogins(self::VALID);
    }

    public function testNumLoginsAliasForGetNumLogins(): void
    {
        $randomInt = random_int(1, 99);
        $handler = $this->getValidHandler();
        $handler->setExpectedValue(self::VALID);
        $handler->setExpectedDesc((string) $randomInt);
        $this->client->setHandler($handler);

        $response = $this->client->numLogins(self::VALID);

        $this->assertEquals($randomInt, $response);
    }
    //endregion NUMLOGINS

    //region UPSDESC
    public function testDescription(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->description(self::VALID);

        $this->assertEquals(self::VALID_DESC, $response);
    }

    public function testDescriptionInvalid(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->description(self::INVALID);
    }

    public function testDescriptionBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->description(self::VALID);
    }
    //endregion UPSDESC

    //region VAR
    public function testGetVar(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->getVar(self::VALID, self::VALID);

        $this->assertEquals(self::VALID_VALUE, $response);
    }

    public function testGetVarServerInfo(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->getVar(self::VALID, 'server.info');
        $serverInfo = sprintf($this->serverInfo, $this->serverVersion);
        $this->assertEquals($serverInfo, $response);
    }

    public function testGetVarServerVersion(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->getVar(self::VALID, 'server.version');

        $this->assertEquals($this->serverVersion, $response);
    }

    public function testGetInvalidServerVar(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->getVar(self::VALID, 'server.software');
    }

    public function testGetAliasForGetVar(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->get(self::VALID, self::VALID);

        $this->assertEquals(self::VALID_VALUE, $response);
    }

    public function testGetVarInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->getVar(self::INVALID, self::VALID);
    }


    public function testGetVarInvalid(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->getVar(self::VALID, self::INVALID);
    }

    public function testGetVarBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->getVar(self::VALID, self::VALID);
    }
    //endregion VAR

    //region TYPE
    public function testVarType(): void
    {
        $validTypes = ['RW','ENUM','STRING','RANGE','NUMBER'];

        $type = 'RW STRING:3';
        $handler = $this->getValidHandler();
        $handler->setExpectedDesc($type);
        $this->client->setHandler($handler);

        $response = $this->client->varType(self::VALID, self::VALID);
        $this->assertEquals($type, $response);

        $values = explode(' ', $response);
        foreach ($values as $value) {
            if (strpos($value, ':') !== false) {
                [$value, $maxLength] = explode(':', $value);
            }
            $this->assertContains($value, $validTypes);
        }
    }

    public function testVarTypeInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->varType(self::INVALID, self::VALID);
    }

    public function testVarTypeInvalidVar(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->varType(self::VALID, self::INVALID);
    }

    public function testVarTypeBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->varType(self::VALID, self::VALID);
    }
    //endregion TYPE

    //region DESC
    public function testVarDescription(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->varDescription(self::VALID, self::VALID);

        $this->assertEquals(self::VALID_DESC, $response);
    }

    public function testVarDescriptionInvalid(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->varDescription(self::INVALID, self::INVALID);
    }

    public function testVarDescriptionBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->varDescription(self::VALID, self::VALID);
    }
    //endregion DESC

    //region CMDDESC
    public function testGetCommandDescription(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->getCommandDescription(self::VALID, self::VALID);

        $this->assertEquals(self::VALID_DESC, $response);
    }

    public function testGetCommandDescriptionInvalid(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->getCommandDescription(self::INVALID, self::INVALID);
    }

    public function testGetCommandDescriptionBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->getCommandDescription(self::VALID, self::VALID);
    }

    public function testCommandDescriptionAliasForGetCommandDescription(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->commandDescription(self::VALID, self::VALID);

        $this->assertEquals(self::VALID_DESC, $response);
    }
    //endregion CMDDESC

    //region TRACKING
    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testGetTrackingInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $this->client->getTracking();
    }

    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    public function testGetTrackingIDInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::TRACKING_ENABLED);
        $handler->setExpectedValue('SUCCESS');
        $this->client->setHandler($handler);

        $faker = Faker::create();
        $uuid = $faker->uuid();

        $this->expectException(InvalidArgument::class);
        $res = $this->client->getTracking($uuid);
        $this->assertEquals('SUCCESS', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testGetTrackingCurrent(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags(0);
        $this->client->setHandler($handler);

        $res = $this->client->getTracking();
        $this->assertEquals('OFF', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testGetTrackingActive(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::TRACKING_ENABLED);
        $this->client->setHandler($handler);

        $res = $this->client->getTracking();
        $this->assertEquals('ON', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testGetTrackingIDValid(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags($handler::TRACKING_ENABLED);
        $handler->setExpectedValue('SUCCESS');
        $this->client->setHandler($handler);

        $faker = Faker::create();
        $uuid = $faker->uuid();

        $res = $this->client->getTracking($uuid);
        $this->assertEquals('SUCCESS', $res);
    }

    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testGetTrackingIDNotConfiguredValidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $handler->initialFlags(0);
        $this->client->setHandler($handler);

        $faker = Faker::create();
        $uuid = $faker->uuid();

        $this->expectException(FeatureNotConfigured::class);
        $this->client->getTracking($uuid);
    }
    //endregion TRACKING
}
