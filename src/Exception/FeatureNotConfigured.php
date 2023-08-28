<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class FeatureNotConfigured extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'FEATURE-NOT-CONFIGURED',
            0,
            null,
            'This instance of upsd hasn\'t been configured properly to allow the requested feature to operate.'
        );
    }
}
