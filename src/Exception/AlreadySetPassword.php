<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class AlreadySetPassword extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'ALREADY-SET-PASSWORD',
            0,
            null,
            'The client already set a PASSWORD and can\'t set another. ' .
            'This also should never happen with normal NUT clients.'
        );
    }
}
