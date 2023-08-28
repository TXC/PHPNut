<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class FeatureNotSupported extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'FEATURE-NOT-SUPPORTED',
            0,
            null,
            'This instance of upsd does not support the requested feature.'
        );
    }
}
