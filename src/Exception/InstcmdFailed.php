<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

use TXC\NUT\Exception\NutException;

class InstcmdFailed extends NutException
{
    public function __construct()
    {
        parent::__construct(
            'INSTCMD-FAILED',
            0,
            null,
            'upsd failed to deliver the instant command request to the driver. ' .
            'No further information is available to the client. This typically indicates a dead or broken driver.'
        );
    }
}
