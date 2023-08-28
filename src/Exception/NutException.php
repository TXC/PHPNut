<?php

declare(strict_types=1);

namespace TXC\NUT\Exception;

class NutException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected string $description = ''
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
