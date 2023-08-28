<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class DriverNotConnected extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'DRIVER-NOT-CONNECTED',
            0,
            null,
            'upsd can\'t perform the requested command, since the driver for that ' .
            'UPS is not connected. This usually means that the driver is not running, ' .
            'or if it is, the ups.conf is misconfigured.'
        );
    }
}
