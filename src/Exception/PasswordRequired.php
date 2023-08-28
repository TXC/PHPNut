<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class PasswordRequired extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'PASSWORD-REQUIRED',
            0,
            null,
            'The requested command requires a passname for authentication, but the client hasn\'t set one.'
        );
    }
}
