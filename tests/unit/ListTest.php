<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\TestWith;
use TXC\NUT\Client;
use TXC\NUT\Exception\InvalidArgument;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\UnknownUps;
use TXC\NUT\Exception\VarNotSupported;
use TXC\NUT\Tests\ClientTestCase;

class ListTest extends ClientTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //$mockHandler = $this->getMockHandler();
        $this->client = new Client(debug: false, connect: false);
        $this->client->setHandler($this->getValidHandler(true));
    }

    public function testInvalidListResult(): void
    {
        //$this->client->setHandler($this->getValidHandler());

        $result = [];
        for ($i = 0; $i < 2; $i++) {
            $result[] = sprintf('ENUM %s "%s"', $this->faker->word(), $this->faker->sentence());
        }
        $handler = $this->getMockHandler(true);
        $handler->method('read')
                ->willReturn([
                    'BEGIN LIST UPS',
                    ...$result,
                    'END LIST UPS',
                ]);

        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $this->client->listUPS();
    }

    //region LIST UPS
    public function testGetUpsList(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->listUPS();

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        $this->assertEquals(self::VALID_UPS_NAME, $response[self::VALID]);
    }

    public function testGetUpsListFaker(): void
    {
        //$this->client->setHandler($this->getValidHandler());

        $random = random_int(5, 10);
        $result = [];
        $words = $this->faker->words($random);
        $words = array_unique($words);
        if (count($words) < $random) {
            $words = array_merge($words, $this->faker->words($random - count($words)));
        }
        foreach ($words as $word) {
            $result[] = sprintf(
                'UPS %s "%s"',
                $word,
                $this->faker->sentence()
            );
        }
        $result[] = sprintf('UPS %s "%s"', self::VALID, self::VALID_DESC);
        shuffle($result);

        $handler = $this->getMockHandler(true);
        $handler->method('read')
                ->willReturn([
                    'BEGIN LIST UPS',
                    ...$result,
                    'END LIST UPS',
                ]);

        $this->client->setHandler($handler);

        $response = $this->client->listUPS();

        $this->assertIsArray($response);
        $this->assertCount($random + 1, $response);
        $this->assertEquals(self::VALID_UPS_NAME, $response[self::VALID]);
    }


    public function testGetUpsListBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listUPS();
    }
    //endregion LIST UPS

    //region LIST VAR
    public function testGetUpsVarsValidUps(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->listVars(self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[self::VALID]);
        $this->assertCount(3, $response[self::VALID]);

        $this->assertArrayHasKey('battery.voltage', $response[self::VALID]);
        $this->assertEquals(self::VALID_DESC, $response[self::VALID][self::VALID]);
    }

    public function testGetUpsVarsInvalidUps(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listVars(self::INVALID);
    }

    public function testGetUpsVarsBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listVars(self::VALID);
    }
    //endregion LIST VAR

    //region LIST RW
    public function testGetRwVarsValidUps(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->listRWVars(self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[self::VALID]);
        $this->assertCount(3, $response[self::VALID]);

        $this->assertArrayHasKey('ups.delay.shutdown', $response[self::VALID]);
        $this->assertEquals(self::VALID_DESC, $response[self::VALID][self::VALID]);
    }

    public function testGetRwVarsInvalidUps(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listRWVars(self::INVALID);
    }

    public function testGetRwVarsBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listRWVars(self::VALID);
    }
    //endregion LIST RW

    //region LIST CMD
    public function testGetUpsCommandsValidUps(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->listCommands(self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[self::VALID]);
        $this->assertCount(3, $response[self::VALID]);

        $this->assertArrayHasKey('load.on', $response[self::VALID]);
        $this->assertEquals(self::VALID_CMD_DESC, $response[self::VALID][self::VALID]);
    }

    public function testGetUpsCommandsNoResults(): void
    {
        //$this->client->setHandler($this->getValidHandler());

        $handler = $this->getMockHandler(true);
        $handler->method('read')
                ->willReturn([
                    'BEGIN LIST CMD',
                    'END LIST CMD',
                ]);

        $this->client->setHandler($handler);

        //$this->expectException(NutException::class);
        $response = $this->client->listCommands($this->faker->word());
        $this->assertIsArray($response);
        $this->assertCount(0, $response);
    }

    public function testGetUpsCommandsInvalidUps(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listCommands(self::INVALID);
    }

    public function testGetUpsCommandsBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listCommands('');
    }
    //endregion LIST CMD

    //region LIST ENUM
    public function testListEnum(): void
    {
        $this->client->setHandler($this->getValidHandler());
        $response = $this->client->listEnum(self::VALID, self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[self::VALID]);
        $this->assertCount(1, $response[self::VALID]);
        //$this->assertContains(self::VALID, $response[self::VALID][self::VALID]);
    }


    public function testListEnumFaker(): void
    {
        $upsName = $this->faker->word();
        $variableName = $this->faker->word();

        $random = random_int(5, 10);
        $result = [];
        for ($i = 0; $i < $random; $i++) {
            $result[] = sprintf(
                'ENUM %s %s "%s"',
                $upsName,
                $variableName,
                $this->faker->word()
            );
        }
        $result[] = sprintf(
            'ENUM %s %s "%s"',
            $upsName,
            $variableName,
            self::VALID
        );
        shuffle($result);

        $handler = $this->getMockHandler(true);
        $handler->method('read')
                ->willReturn([
                    'BEGIN LIST ENUM',
                    ...$result,
                    'END LIST ENUM',
                ]);

        $this->client->setHandler($handler);

        $response = $this->client->listEnum($upsName, $variableName);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[$upsName]);
        $this->assertCount(1, $response[$upsName]);

        $this->assertIsArray($response[$upsName][$variableName]);
        $this->assertCount($random + 1, $response[$upsName][$variableName]);
        $this->assertContains(self::VALID, $response[$upsName][$variableName]);
    }

    public function testListEnumInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listEnum(self::INVALID, self::VALID);
    }

    public function testListEnumInvalidVar(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listEnum(self::VALID, self::INVALID);
    }

    public function testListEnumBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listEnum(self::VALID, self::VALID);
    }
    //endregion LIST ENUM

    //region LIST RANGE
    public function testListRange(): void
    {
        $handler = $this->getValidHandler();
        $handler->setExpectedDesc(self::VALID_DESC);
        $this->client->setHandler($handler);
        $response = $this->client->listRange(self::VALID, self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertIsArray($response[self::VALID]);
        $this->assertCount(2, $response[self::VALID]);

        $this->assertNotEmpty($response[self::VALID]['min']);
        $this->assertNotEmpty($response[self::VALID]['max']);

        $this->assertEquals(self::VALID_DESC, $response[self::VALID]['min']);
        $this->assertEquals(self::VALID_DESC, $response[self::VALID]['max']);
    }

    public function testListRangeInvalidUPS(): void
    {
        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listRange(self::INVALID, self::VALID);
    }

    public function testListRangeInvalidVar(): void
    {
        $this->expectException(VarNotSupported::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listRange(self::VALID, self::INVALID);
    }

    public function testListRangeBroken(): void
    {
        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listRange(self::VALID, self::VALID);
    }
    //endregion LIST RANGE

    //region LIST CLIENT
    #[TestWith(['1.1'])]
    #[TestWith(['1.8.0'])]
    #[TestWith(['2.5.0'])]
    #[TestWith(['2.6.0'])]
    public function testListClientsInvalidVersion(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(InvalidArgument::class);
        $response = $this->client->listClients(self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertCount(3, $response[self::VALID]);
        $this->assertArrayHasKey(self::VALID, $response);
        $this->assertContains(self::VALID, $response[self::VALID]);
    }

    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testListClientsValid(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $response = $this->client->listClients(self::VALID);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertCount(3, $response[self::VALID]);
        $this->assertArrayHasKey(self::VALID, $response);
        $this->assertContains(self::VALID, $response[self::VALID]);
    }

    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testListClientsInvalidUPS(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);


        $this->expectException(UnknownUps::class);
        $this->client->setHandler($this->getValidHandler());
        $this->client->listClients(self::INVALID);
    }

    #[TestWith(['2.6.4'])]
    #[TestWith(['2.7.4'])]
    #[TestWith(['2.8.0'])]
    #[TestWith(['2.8.6'])]
    public function testListClientsBroken(string $serverVersion): void
    {
        $handler = $this->getValidHandler(true);
        $handler->setServerVersion($serverVersion);
        $this->client->setHandler($handler);

        $this->expectException(NutException::class);
        $this->client->setHandler($this->getBrokenHandler());
        $this->client->listClients(self::VALID);
    }
    //endregion LIST CLIENT
}
