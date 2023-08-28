<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class TooLong extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'TOO-LONG',
            0,
            null,
            'The requested value in a SET command is too long.'
        );
    }
}
