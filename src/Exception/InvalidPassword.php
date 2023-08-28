<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class InvalidPassword extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'INVALID-PASSWORD',
            0,
            null,
            'The client sent an invalid PASSWORD — perhaps an empty one.'
        );
    }
}
