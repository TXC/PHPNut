<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit\Telnet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TXC\NUT\Telnet\Option;

class OptionTest extends TestCase
{
    private static function commandProvider(): array
    {
        $result = [];
        foreach (Option::cases() as $var) {
            $result[] = [$var->value];
        }
        return $result;
    }

    public static function valueProvider(): array
    {
        return array_values(self::commandProvider());
    }

    public static function nameProvider(): array
    {
        return array_keys(self::commandProvider());
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function testByValue(int $value): void
    {
        $expected = chr($value);
        $actual = Option::from($value);

        $this->assertEquals($expected, $actual->chr());
        $this->assertSame($expected, $actual->chr());
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function testByInvalidValue(int $value): void
    {
        $actual = Option::tryFrom($value ^ 0x40);
        $this->assertNull($actual);
    }
}
