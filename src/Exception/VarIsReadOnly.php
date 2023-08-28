<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class VarIsReadOnly extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'READONLY',
            0,
            null,
            'The requested variable in a SET command is not writable.'
        );
    }
}
