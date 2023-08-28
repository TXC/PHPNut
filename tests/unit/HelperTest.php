<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use Faker\Factory as Faker;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use TXC\NUT\Helper;
use TXC\NUT\Tests\ClientTestCase;

class HelperTest extends ClientTestCase
{
    protected function setUp(): void
    {
        $this->faker = Faker::create();
    }

    #[RunInSeparateProcess]
    public function testHelperLogWithName(): void
    {
        $firstName = $this->faker->word();
        $secondName = $this->faker->word();
        $expectedName = $firstName . '.' . $secondName;

        $first = Helper::getLogger($firstName);
        $second = Helper::log($secondName);

        $this->assertEquals($firstName, $first->getName());
        $this->assertEquals($expectedName, $second->getName());
        $this->assertNotSame($first, $second);
    }

    #[RunInSeparateProcess]
    public function testHelperLogWithoutName(): void
    {
        $firstName = $this->faker->word();
        $expectedName = $firstName;

        $first = Helper::getLogger($firstName);
        $second = Helper::log();

        $this->assertEquals($firstName, $first->getName());
        $this->assertEquals($expectedName, $second->getName());
    }

    #[RunInSeparateProcess]
    public function testHelperLogWithInstance(): void
    {
        $firstName = $this->faker->word();
        $first = Helper::getLogger($firstName);
        $second = Helper::log();
        $this->assertInstanceOf(Logger::class, $first);
        $this->assertInstanceOf(Logger::class, $second);
        $this->assertEquals($first->getName(), $second->getName());
        $this->assertSame($first, $second);
    }

    #[RunInSeparateProcess]
    public function testHelperLogWithoutInstance(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $instance = Helper::log();
        $this->assertInstanceOf(Logger::class, $instance);
    }

    #[RunInSeparateProcess]
    public function testHelperGetLogger(): void
    {
        $firstName = $this->faker->word();
        $first = Helper::getLogger($firstName);
        $this->assertInstanceOf(Logger::class, $first);
    }

    #[RunInSeparateProcess]
    public function testHelperGetLoggerWithNewName(): void
    {
        $firstName = $this->faker->word();
        $first = Helper::getLogger($firstName);
        $this->assertInstanceOf(Logger::class, $first);
        $this->assertEquals($first->getName(), $firstName);
    }

    #[RunInSeparateProcess]
    public function testHelperGetLoggerNewInstance(): void
    {
        $firstName = $this->faker->word();
        $secondName = $this->faker->word();
        $first = Helper::getLogger($firstName);
        $second = Helper::getLogger($secondName);

        $this->assertInstanceOf(Logger::class, $first);
        $this->assertInstanceOf(Logger::class, $second);
        $this->assertEquals($first->getName(), $firstName);
        $this->assertEquals($second->getName(), $secondName);
        $this->assertNotSame($first, $second);
    }

    #[RunInSeparateProcess]
    public function testHelperGetLoggerWithDebug(): void
    {
        $firstName = $this->faker->word();
        $first = Helper::getLogger($firstName, true);

        $this->assertInstanceOf(Logger::class, $first);
        $this->assertEquals($first->getName(), $firstName);
    }
}
