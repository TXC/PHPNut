<?php

declare(strict_types=1);

namespace TXC\NUT;

use Monolog\Logger;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception\ConnectionExistsException;
use TXC\NUT\Handler\Stream;

abstract class AbstractClient
{
    protected Logger $logger;

    /**
     * @param AbstractHandler|null $handler Transportation Handler for every request/response.
     * @param bool $debug Boolean, put class in debug mode (prints everything on console, defaults to False)
     * @param bool $connect Automatically connect on initialization
     * @throws NutException
     */
    public function __construct(
        protected ?AbstractHandler $handler = null,
        bool $debug = false,
        bool $connect = true,
    ) {
        $this->logger = Helper::getLogger('client', $debug);
        if ($connect && $handler !== null) {
            $this->connect();
        }
    }

    /**
     * @throws ConnectionExistsException
     * @throws NutException
     */
    public function __destruct()
    {
        if (!empty($this->handler)) {
            $this->getHandler()->write('LOGOUT');
            $this->getHandler()->close();
            unset($this->handler);
        }
    }

    public function setHandler(AbstractHandler $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * @return AbstractHandler
     * @throws ConnectionExistsException
     * @throws NutException
     */
    public function getHandler(): ?AbstractHandler
    {
        if (!($this->handler instanceof AbstractHandler)) {
            throw new NutException('No handler set');
        }
        return $this->handler;
    }

    /**
     * Connects to the defined server.
     *
     * If login/pass was specified, the class tries to authenticate.
     * An error is raised if something goes wrong.
     * @throws NutException
     */
    public function connect(): void
    {
        $this->logger->debug('Connecting to host');

        try {
            $this->getHandler()?->open();
        } catch (NutException $e) {
            $this->logger->error($e->getMessage());
            throw new NutException('Socket error', 0, $e);
        }
    }
}
