<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class UnknownCommand extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'UNKNOWN-COMMAND',
            0,
            null,
            'upsd doesn\'t recognize the requested command.'
        );
    }
}
