<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class InvalidArgument extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'INVALID-ARGUMENT',
            0,
            null,
            'The client sent an argument to a command which is not recognized or ' .
            'is otherwise invalid in this context. This is typically caused by ' .
            'sending a valid command like GET with an invalid subcommand.'
        );
    }
}
