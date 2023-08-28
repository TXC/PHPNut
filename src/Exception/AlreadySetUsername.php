<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class AlreadySetUsername extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'ALREADY-SET-USERNAME',
            0,
            null,
            'The client has already set a USERNAME, and can\'t set another. ' .
            'This should never happen with normal NUT clients.'
        );
    }
}
