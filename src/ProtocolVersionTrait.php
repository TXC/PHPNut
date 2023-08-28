<?php

namespace TXC\NUT;

use InvalidArgumentException;
use TXC\NUT\Exception\NutException;

trait ProtocolVersionTrait
{
    protected function getProtocolVersion(?string $version = null): string
    {
        if ($version === null && !empty($this->serverVersion)) {
            $version = $this->serverVersion;
        }
        return match (true) {
            version_compare($version, '2.8.0', '>=') => '1.3',
            version_compare($version, '2.6.4', '>=') => '1.2',
            version_compare($version, '1.5.0', '>=') => '1.1',
            version_compare($version, '1.5.0', '<') => '1.0',
            default => throw new NutException('Invalid version number'),
        };
    }
}
