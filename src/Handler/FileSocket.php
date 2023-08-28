<?php

declare(strict_types=1);

namespace TXC\NUT\Handler;

use TXC\NUT\Exception\ConnectionExistsException;
use TXC\NUT\Exception\NutException;
use TXC\NUT\AbstractHandler;
use TXC\NUT\Telnet\DoublerTrait;
use TXC\NUT\Telnet\Command;

// @codeCoverageIgnoreStart
class FileSocket extends AbstractHandler
{
    use DoublerTrait;

    /** @var resource|null */
    protected $sock;

    /**
     * @inheritDoc
     * @throws NutException
     * @throws ConnectionExistsException
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

        $this->host = sprintf('%s://%s', $protocol, $this->host);
        $this->sock = fsockopen($this->host, $this->port, $errno, $error, $this->timeout);
        if ($$this->sock === false) {
            $this->logger->error('[{errno}]: {error}', ['errno' => $errno, 'error' => $error]);
            throw new NutException($error, $errno);
        }
    }

    /**
     * @inheritDoc
     */
    protected function disconnect($sock = null): void
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
        $res = fwrite($this->sock, $buffer, $bufferSize);
        if ($res === false) {
            $this->logger->error('Unable to write data to socket');
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
        while (!feof($this->sock)) {
            $result[] = $this->readLine();
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

        $res = fgets($this->sock);
        if ($res === false) {
            $this->logger->error('Unable to read from socket');
            return '';
        }
        return $res;
    }
}
// @codeCoverageIgnoreEnd
