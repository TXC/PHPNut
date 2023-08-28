<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class InvalidValue extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'INVALID-VALUE',
            0,
            null,
            'The value specified in the request is not valid. ' .
            'This usually applies to a SET of an ENUM type which is using a value ' .
            'which is not in the list of allowed values.'
        );
    }
}
