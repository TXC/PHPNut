<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class CmdNotSupported extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'CMD-NOT-SUPPORTED',
            0,
            null,
            'The specified UPS doesn\'t support the instant command in the request.'
        );
    }
}
