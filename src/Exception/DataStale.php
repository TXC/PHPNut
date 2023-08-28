<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class DataStale extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'DATA-STALE',
            0,
            null,
            'upsd is connected to the driver for the UPS, but that driver isn\'t ' .
            'providing regular updates or has specifically marked the data as stale. ' .
            'upsd refuses to provide variables on stale units to avoid false readings.'
        );
    }
}
