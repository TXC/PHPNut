<?php

declare(strict_types=1);

namespace TXC\NUT\Handler;

use TXC\NUT\Exception\ConnectionExistsException;
use TXC\NUT\Exception\NutException;
use TXC\NUT\AbstractHandler;
use TXC\NUT\Telnet\DoublerTrait;
use TXC\NUT\Telnet\Command;

// @codeCoverageIgnoreStart
class Stream extends AbstractHandler
{
    use DoublerTrait;

    /** @var resource|null */
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
            $protocol = 'tls';
        } else {
            $protocol = 'tcp';
        }
        if (empty($this->host) || empty($this->port)) {
            throw new \InvalidArgumentException('Expect atleast ');
        }

        $this->host = sprintf('%s://%s:%d', $protocol, $this->host, $this->port);
        $sock = stream_socket_client($this->host, $errno, $error, $this->timeout);
        if ($sock === false) {
            $this->logger->error('[{errno}]: {error}', ['errno' => $errno, 'error' => $error]);
            $this->sock = null;
            throw new NutException($error, $errno);
        }
        $this->sock = $sock;
        $res = stream_set_timeout($this->sock, $this->timeout);
        if ($res === false) {
            $this->logger->warning('Unable to set timeout on R/W');
        }

        $res = stream_set_blocking($this->sock, false);
        if ($res === false) {
            $this->logger->warning('Unable to switch to non-blocking mode');
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect($sock = null): void
    {
        if (!empty($sock)) {
            fclose($sock);
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
        $res = stream_socket_sendto($this->sock, $buffer);
        //$res = fwrite($this->sock, $buffer, $bufferSize);

        if ($res === -1) {
            $this->logger->error('Unable to write data to socket');
        }
        if ($res == $bufferSize) {
            $this->logger->warning('Bytes written is not same as payload size');
        }
    }

    /**
     * @inheritDoc
     */
    public function read(string $ending = "\n"): array
    {
        if (empty($this->sock)) {
            throw new NutException('Not connected');
        }

        $result = [];
        while (($line = stream_get_line($this->sock, 0, $ending))  !==  false) {
            $result[] = $line;
        }
        return $result;
    }
}
// @codeCoverageIgnoreEnd
