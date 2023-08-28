<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class AlreadySSLMode extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'ALREADY-SSL-MODE',
            0,
            null,
            'TLS/SSL mode is already enabled on this connection, so upsd can\'t start it again.'
        );
    }
}
