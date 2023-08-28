<?php

namespace TXC\NUT;

use TXC\NUT\Exception\ConnectionExistsException;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Telnet\Command;
use TXC\NUT\Helper;
use Monolog\Logger;

abstract class AbstractHandler
{
    // Telnet protocol defaults
    public const TELNET_PORT = 23;

    /** @var mixed */
    protected $sock = null;
    protected Logger $logger;

    /**
     * Constructor.
     *
     * When called without arguments, create an unconnected instance.
     * With a hostname argument, it connects the instance; port number,
     * timeout and secure are optional.
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param bool $secure
     * @throws ConnectionExistsException
     * @throws NutException
     */
    public function __construct(
        protected string $host = '127.0.0.1',
        protected int $port = self::TELNET_PORT,
        protected int $timeout = 5,
        protected bool $secure = false,
    ) {
        $this->logger = Helper::log('handler');
        $this->sock = null;
        if (!empty($this->host)) {
            $this->open();
        }
    }

    /**
     * Destructor -- close the connection.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Connect to a host.
     *
     * Don't try to reopen an already connected instance.
     *
     * @return void
     */
    final public function open(): void
    {
        if (!empty($this->sock)) {
            $this->logger->notice('Socket already opened');
            return;
        }
        if (empty($this->host) || empty($this->port)) {
            throw new \InvalidArgumentException('Invalid host / port for connection');
        }
        $this->connect();
    }

    /**
     * Disconnect from host
     */
    final public function close(): void
    {
        if (!empty($this->sock)) {
            $sock = $this->sock;
            $this->sock = null;
            unset($this->sock);
            $this->disconnect($sock);
        }
    }

    /**
     * Return the socket object used internally.
     * @return mixed
     */
    public function getSocket()
    {
        if (empty($this->sock)) {
            return null;
        }

        return $this->sock;
    }

    /**
     * Write a single string to the socket, doubling any IAC characters.
     * And return the response
     *
     * Can block if the connection is blocked.
     *
     * @param string $buffer String to send to server
     * @param mixed $values Values to format $buffer
     * @return array
     */
    public function send(string $buffer, ...$values): array
    {
        if (empty($this->sock)) {
            $this->connect();
        }

        $this->write($buffer, ...$values);
        $result = $this->read();

        return $result;
    }

    /**
     * Establish a connection to host
     */
    abstract protected function connect(): void;

    /**
     * Closes the connection.
     */
    abstract protected function disconnect($sock = null): void;

    /**
     * Write a string to the socket, doubling any IAC characters.
     *
     * Can block if the connection is blocked.
     *
     * @param string $buffer String to send to server
     * @param mixed $values Values to format $buffer
     */
    abstract public function write(string $buffer, ...$values): void;

    /**
     * Reads until EOL, separates by newline into array
     */
    abstract public function read(): array;
}
