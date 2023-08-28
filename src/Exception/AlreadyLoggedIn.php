<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class AlreadyLoggedIn extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'ALREADY-LOGGED-IN',
            0,
            null,
            'The client already sent LOGIN for a UPS and can\'t do it again. ' .
            'There is presently a limit of one LOGIN record per connection.'
        );
    }
}
