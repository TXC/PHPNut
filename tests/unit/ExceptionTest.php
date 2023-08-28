<?php

declare(strict_types=1);

namespace TXC\NUT\Tests\unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public static function valueProvider(): array
    {
        return [
            ['ACCESS-DENIED', \TXC\NUT\Exception\AccessDenied::class],
            ['ALREADY-LOGGED-IN', \TXC\NUT\Exception\AlreadyLoggedIn::class],
            ['ALREADY-SSL-MODE', \TXC\NUT\Exception\AlreadySSLMode::class],
            ['ALREADY-SET-PASSWORD', \TXC\NUT\Exception\AlreadySetPassword::class],
            ['ALREADY-SET-USERNAME', \TXC\NUT\Exception\AlreadySetUsername::class],
            ['CMD-NOT-SUPPORTED', \TXC\NUT\Exception\CmdNotSupported::class],
            ['DATA-STALE', \TXC\NUT\Exception\DataStale::class],
            ['DRIVER-NOT-CONNECTED', \TXC\NUT\Exception\DriverNotConnected::class],
            ['FEATURE-NOT-CONFIGURED', \TXC\NUT\Exception\FeatureNotConfigured::class],
            ['FEATURE-NOT-SUPPORTED', \TXC\NUT\Exception\FeatureNotSupported::class],
            ['INSTCMD-FAILED', \TXC\NUT\Exception\InstcmdFailed::class],
            ['INVALID-ARGUMENT', \TXC\NUT\Exception\InvalidArgument::class],
            ['INVALID-PASSWORD', \TXC\NUT\Exception\InvalidPassword::class],
            ['INVALID-USERNAME', \TXC\NUT\Exception\InvalidUsername::class],
            ['INVALID-VALUE', \TXC\NUT\Exception\InvalidValue::class],
            ['PASSWORD-REQUIRED', \TXC\NUT\Exception\PasswordRequired::class],
            ['READONLY', \TXC\NUT\Exception\VarIsReadOnly::class],
            ['SET-FAILED', \TXC\NUT\Exception\SetFailed::class],
            ['TOO-LONG', \TXC\NUT\Exception\TooLong::class],
            ['UNKNOWN-COMMAND', \TXC\NUT\Exception\UnknownCommand::class],
            ['UNKNOWN-UPS', \TXC\NUT\Exception\UnknownUps::class],
            ['USERNAME-REQUIRED', \TXC\NUT\Exception\UsernameRequired::class],
            ['VAR-NOT-SUPPORTED', \TXC\NUT\Exception\VarNotSupported::class],
        ];
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function testExceptions(string $message, string $className): void
    {
        $class = new $className();
        $this->assertEquals($message, $class->getMessage());
        $this->assertNotNull($class->getDescription());
        $this->assertIsString($class->getDescription());
    }

    public function testNutException(): void
    {
        $message = 'Hello World';
        $code = 12345;
        $description = 'Oh my!';

        $class = new \TXC\NUT\Exception\NutException($message, $code, null, $description);
        $this->assertEquals($message, $class->getMessage());
        $this->assertEquals($code, $class->getCode());
        $this->assertEquals($description, $class->getDescription());
        $this->assertNotNull($class->getDescription());
        $this->assertIsString($class->getDescription());
    }

    public function testNutExceptionWithoutDescription(): void
    {
        $message = 'Hello World';
        $code = 12345;

        $class = new \TXC\NUT\Exception\NutException($message, $code);
        $this->assertEquals($message, $class->getMessage());
        $this->assertEquals($code, $class->getCode());
        $this->assertEquals('', $class->getDescription());
    }

    public function testConnectionExistsException(): void
    {
        $class = new \TXC\NUT\Exception\ConnectionExistsException();
        $this->assertNotEmpty($class->getMessage());
        $this->assertIsString($class->getMessage());
    }
}
