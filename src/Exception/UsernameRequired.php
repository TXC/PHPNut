<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class UsernameRequired extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'USERNAME-REQUIRED',
            0,
            null,
            'The requested command requires a username for authentication, but the client hasn\'t set one.'
        );
    }
}
