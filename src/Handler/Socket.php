<?php

declare(strict_types=1);

namespace TXC\NUT\Handler;

use Socket as PHPSocket;
use TXC\NUT\Exception\ConnectionExistsException;
use TXC\NUT\Exception\NutException;
use TXC\NUT\AbstractHandler;
use TXC\NUT\Helper;
use TXC\NUT\Telnet\DoublerTrait;
use TXC\NUT\Telnet\Command;

// @codeCoverageIgnoreStart
class Socket extends AbstractHandler
{
    use DoublerTrait;

    /** @var PHPSocket|null */
    protected $sock;

    /**
     * @inheritDoc
     */
    public function connect(): void
    {
        if ($this->sock !== null) {
            throw new ConnectionExistsException();
        }
        if ($this->secure === true) {
            throw new NutException('This handler doesn\'t support secure');
        }

        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->sock, $this->host, $this->port);
        socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
        socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
    }

    /**
     * @inheritDoc
     */
    public function disconnect($sock = null): void
    {
        if (!empty($sock)) {
            socket_close($sock);
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $buffer, ...$values): void
    {
        if (empty($this->sock)) {
            throw new NutException('Not connected');
        }

        if (!empty($values)) {
            $buffer = sprintf($buffer, ...$values);
        }
        $buffer = $this->doubleCharacter($buffer, Command::IAC);

        $bufferSize = strlen($buffer);
        // MSG_OOB: 1, MSG_EOR: 128, MSG_EOF: 512, MSG_DONTROUTE: 4
        $res = socket_send($this->sock, $buffer, $bufferSize, 0);
        if ($res === false) {
            $errno = socket_last_error($this->sock);
            $this->logger->error('[{errno}]: {error}', ['errno' => $errno, 'error' => socket_strerror($errno)]);
        }
    }

    /**
     * @inheritDoc
     */
    public function read(): array
    {
        if (empty($this->sock)) {
            throw new NutException('Not connected');
        }

        $result = [];
        while (true) {
            $res = $this->readLine();
            if ($res === '') {
                break;
            }
            $result[] = $res;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function readLine(): string
    {
        if (empty($this->sock)) {
            throw new NutException('Not connected');
        }

        $res = socket_read($this->sock, 512);
        if ($res === false) {
            $errno = socket_last_error($this->sock);
            $this->logger->error('[{errno}]: {error}', ['errno' => $errno, 'error' => socket_strerror($errno)]);
            return '';
        }
        if (str_contains($res, "\n")) {
            $res = explode("\n", $res);
            $res = $res[0] . "\n";
        } else {
            $res .= $this->readLine();
        }
        return $res;
    }
}
// @codeCoverageIgnoreEnd
