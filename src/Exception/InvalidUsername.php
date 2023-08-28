<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class InvalidUsername extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'INVALID-USERNAME',
            0,
            null,
            'The client sent an invalid USERNAME.'
        );
    }
}
