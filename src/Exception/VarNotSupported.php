<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class VarNotSupported extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'VAR-NOT-SUPPORTED',
            0,
            null,
            'The specified UPS doesn\'t support the variable in the request.'
        );
    }
}
