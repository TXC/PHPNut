<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class UnknownUps extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'UNKNOWN-UPS',
            0,
            null,
            'The UPS specified in the request is not known to upsd. ' .
            'This usually means that it didn\'t match anything in ups.conf.'
        );
    }
}
