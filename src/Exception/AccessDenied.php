<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class AccessDenied extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'ACCESS-DENIED',
            0,
            null,
            'The client\'s host and/or authentication details (username, password) ' .
            'are not sufficient to execute the requested command.'
        );
    }
}
