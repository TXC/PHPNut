<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

class ConnectionExistsException extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'Connection already exists. Please close it before opening a new one.',
        );
    }
}
