<?php

declare(strict_types=1);

namespace TXC\NUT\Tests;

use TXC\NUT\AbstractHandler;
use TXC\NUT\Helper;
use TXC\NUT\ProtocolVersionTrait;
use TXC\NUT\Telnet\DoublerTrait;
use TXC\NUT\Telnet\Command;

class MockServer extends AbstractHandler
{
    use DoublerTrait;
    use ProtocolVersionTrait;

    public const USERNAME_SENT = (1 << 0);
    public const PASSWORD_SENT = (1 << 1);
    public const UPS_LOGIN = (1 << 2);
    public const TLS_ENABLED = (1 << 3);
    public const TRACKING_ENABLED = (1 << 4);
    public const IS_BROKEN = (1 << 5);
    public const IS_OK = (1 << 6);
    public const USERNAME_IS_BROKEN = (1 << 7);
    private string $command;
    //private bool $isBroken = false;
    //private bool $isOk = true;
    //private bool $usernameIsBroken = false;
    //private bool $trackingEnabled = false;
    //private bool $startTLSEnabled = false;
    //private bool $upsLogin = false;
    private int $flags = 0;
    private string $expectedValue;
    private string $expectedDesc;
    private string $serverVersion = '2.7.4';
    private string $errorCode = '';

    public function __construct(
        string $host = '127.0.0.1',
        int $port = self::TELNET_PORT,
        int $timeout = 5,
        bool $secure = false,
        string $serverVersion = '2.7.4',
    ) {
        parent::__construct($host, $port, $timeout, $secure);
        $this->setFlag(self::IS_BROKEN, false);
        $this->setFlag(self::IS_OK, true);
        $this->setServerVersion($serverVersion);

        $debug = debug_backtrace(0, 3);
        $handlerName = str_replace('\\', '.', $debug[2]['class']) . '.' . $debug[2]['function'];
        $this->logger = Helper::log('handler.' . $handlerName);
    }

    public function connect(): void
    {
        $this->sock = random_int(1, PHP_INT_MAX);
        //$this->logger->info('*** CONNECT ***');
        //$this->showFlags();
    }

    /**
     * @return void
     */
    public function disconnect($sock = null): void
    {
        //$this->logger->info('*** DISCONNECT ***');
        //$this->showFlags();
    }

    /**
     * @param string $buffer
     * @param ...$values
     * @return void
     */
    public function write(string $buffer, ...$values): void
    {
        $buffer = sprintf($buffer, ...$values);
        $this->command = $this->doubleCharacter($buffer, Command::IAC);
        $this->logger->debug('Executing: ' . $this->command);
    }

    /**
     * @return array
     */
    public function read(): array
    {
        return $this->runCommand();
    }

    public function initialFlags(int $flags = 0): void
    {
        if ($flags > 0) {
            $this->setFlag($flags, true);
        } else {
            $this->flags = 0;
        }
    }

    private function setFlag(int $flag, bool $state): void
    {
        $this->logger->debug('FLAG: {0} = {1}', [$this->bin($flag), (int) $state]);
        $oldValue = $this->flags;
        if ($state === true) {
            $value = ($oldValue | $flag);
        } else {
            $value = ($oldValue & ~$flag);
        }
        if ($oldValue === $value) {
            return;
        }
        $this->flags = $value;
    }

    private function getFlag(int $flag): bool
    {
        if (
            $flag === self::TRACKING_ENABLED
            && version_compare($this->serverVersion, '2.8.0', '<')
        ) {
            $this->errorAvailableFrom('TRACKING', '2.8.0');
            return false;
        }
        return (($this->flags & $flag) > 0) ? true : false;
    }

    protected function showFlags(): void
    {
        $this->logger->info('- - - - - - - - - - ---------- - - - - - -');
        $this->logger->info('FLAGS:              {0}', [$this->bin($this->flags)]);
        $this->logger->info('- - - - - - - - - - ---------- - - - - - -');
        $this->logger->info('USERNAME_SENT:      {0}', [$this->bin(self::USERNAME_SENT)]);
        $this->logger->info('PASSWORD_SENT:      {0}', [$this->bin(self::PASSWORD_SENT)]);
        $this->logger->info('UPS_LOGIN:          {0}', [$this->bin(self::UPS_LOGIN)]);
        $this->logger->info('TLS_ENABLED:        {0}', [$this->bin(self::TLS_ENABLED)]);
        $this->logger->info('TRACKING_ENABLED:   {0}', [$this->bin(self::TRACKING_ENABLED)]);
        $this->logger->info('IS_BROKEN:          {0}', [$this->bin(self::IS_BROKEN)]);
        $this->logger->info('IS_OK:              {0}', [$this->bin(self::IS_OK)]);
        $this->logger->info('USERNAME_IS_BROKEN: {0}', [$this->bin(self::USERNAME_IS_BROKEN)]);
        $this->logger->info('- - - - - - - - - - ---------- - - - - - -');
    }

    public function setIsBroken(bool $state): void
    {
        $this->setFlag(self::IS_BROKEN, $state);
    }

    public function setUsernameIsBroken(bool $state): void
    {
        $this->setFlag(self::USERNAME_IS_BROKEN, $state);
    }

    public function setIsOk(bool $state): void
    {
        $this->setFlag(self::IS_OK, $state);
    }

    public function setExpectedValue(string $value): void
    {
        $this->expectedValue = $value;
    }

    public function setExpectedDesc(string $description): void
    {
        $this->expectedDesc = $description;
    }

    public function setServerVersion(string $version): void
    {
        $this->serverVersion = $version;
    }

    public function setErrorCode(string $code): void
    {
        $this->errorCode = $code;
    }

    public function showServerInfo(): void
    {
        $this->logger->info('SERVER STRING:      ' . $this->getServerInfo());
        $this->logger->info('SERVER VERSION:     ' . $this->serverVersion);
        $this->logger->info('PROTOCOL VERSION:   ' . $this->getProtocolVersion());
        $this->showFlags();
    }

    private function nutUUIDv4(): string
    {
        $uuid = [];
        for ($i = 0; $i < 16; $i++) {
            $uuid[$i] = abs(rand(-128, 127) + rand(-128, 127));
        }

        /* set variant and version */
        $uuid[6] = ($uuid[6] & 0x0F) | 0x40;
        $uuid[8] = ($uuid[8] & 0x3F) | 0x80;

        return sprintf(
            "%02X%02X%02X%02X-%02X%02X-%02X%02X-%02X%02X-%02X%02X%02X%02X%02X%02X",
            $uuid[0],
            $uuid[1],
            $uuid[2],
            $uuid[3],
            $uuid[4],
            $uuid[5],
            $uuid[6],
            $uuid[7],
            $uuid[8],
            $uuid[9],
            $uuid[10],
            $uuid[11],
            $uuid[12],
            $uuid[13],
            $uuid[14],
            $uuid[15]
        );
    }

    private function bool2OnOff(bool $value): string
    {
        return $value ? 'ON' : 'OFF';
    }

    private function parseArgument(): array
    {
        $arguments = explode(' ', $this->command);
        $result = [];
        $pos = 0;
        $inString = false;
        foreach ($arguments as $argument) {
            if (strpos($argument, '"') !== false) {
                $inString = !$inString;
            }
            if (!empty($result[$pos])) {
                $result[$pos] .= ' ' . trim($argument);
            } else {
                $result[$pos] = trim($argument);
            }
            if (!$inString) {
                $pos++;
            }
        }
        return $result;
    }

    private function bin(int $value): string
    {
        return '0b' . str_pad(base_convert((string) $value, 10, 2), 8, '0', STR_PAD_LEFT);
    }

    private function checkAuth(): array
    {
        if ($this->getFlag(self::USERNAME_SENT) !== true) {
            $this->logger->error('checkAuth - Username not sent');
            return ['ERR ACCESS-DENIED'];
        }
        if ($this->getFlag(self::PASSWORD_SENT) !== true) {
            $this->logger->error('checkAuth - Password not sent');
            return ['ERR ACCESS-DENIED'];
        }
        return [];
    }

    private function errorAvailableFrom(string $command, string $version): array
    {
        $this->logger->error(
            '{0} available from version \'{1}\'. ' .
            'You are running \'{2}\'',
            [
                $command,
                $version,
                $this->serverVersion,
            ]
        );
        return ['ERR INVALID-ARGUMENT'];
    }

    private function getServerInfo(): string
    {
        return sprintf(
            'Network UPS Tools upsd %s - http://www.networkupstools.org/',
            $this->getServerVersion()
        );
    }

    private function getServerVersion(): string
    {
        return $this->serverVersion;
    }

    private function runCommand(): array
    {
        $arguments = $this->parseArgument();

        if (
            $this->getFlag(self::IS_BROKEN) === true
            && $this->getFlag(self::USERNAME_IS_BROKEN) === false
            && $this->command == sprintf('USERNAME %s', $this->expectedValue)
        ) {
            return ['OK'];
        }
        if ($this->getFlag(self::IS_BROKEN) === true) {
            if (!empty($this->errorCode)) {
                return ['ERR ' . $this->errorCode];
            }
            return ['ERR'];
        }

        $commands = [
            'VER' => 'runMiscCommand',
            'NETVER' => 'runMiscCommand',
            /* aliased since NUT 2.8.0 */
            'PROTVER' => 'runMiscCommand',
            'HELP' => 'runMiscCommand',
            'STARTTLS' => 'runMiscCommand',
            'GET' => 'runGetCommand',
            'LIST' => 'runListCommand',
            'USERNAME' => 'runUserCommand',
            'PASSWORD' => 'runUserCommand',
            'LOGIN' => 'runUserCommand', // Requires active login
            'LOGOUT' => 'runUserCommand',
            /* NOTE: Protocol in NUT 2.8.0 allows to handle
             * master/primary to rename/alias the routine.
             */
            'PRIMARY' => 'runUserCommand',  // Requires active login
            'MASTER' => 'runUserCommand',  // Requires active login
            'FSD' => 'runMiscCommand',  // Requires active login
            'SET' => 'runSetCommand',  // Requires active login
            'INSTCMD' => 'runMiscCommand',  // Requires active login
        ];

        $command = array_shift($arguments);
        $result = ['ERR UNKNOWN-COMMAND'];
        if (isset($commands[$command])) {
            $method = $commands[$command];
            $result = $this->{$method}($command, $arguments);
        }
        $this->logger->debug('"' . $this->command . '" RESPONSE: ', $result);

        return $result;
    }

    private function runUserCommand(string $command, array $arguments)
    {
        $result = [];
        switch ($command) {
            case 'USERNAME':
                if (count($arguments) !== 1) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }
                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR INVALID-USERNAME'];
                }
                if ($this->getFlag(self::USERNAME_SENT) === true) {
                    $this->logger->error('Username already sent');
                    return ['ERR ALREADY-SET-USERNAME'];
                }
                $this->setFlag(self::USERNAME_SENT, true);
                $result[] = 'OK';
                break;
            case 'PASSWORD':
                if (count($arguments) !== 1) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }
                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR INVALID-PASSWORD'];
                }
                if ($this->getFlag(self::PASSWORD_SENT) === true) {
                    $this->logger->error('Password already sent');
                    return ['ERR ALREADY-SET-PASSWORD'];
                }
                $this->setFlag(self::PASSWORD_SENT, true);
                $result[] = 'OK';
                break;
            case 'PRIMARY':
                if (version_compare($this->serverVersion, '2.8.0', '<')) {
                    return $this->errorAvailableFrom('PRIMARY', '2.8.0');
                }
                // no break
            case 'MASTER':
                $check = $this->checkAuth();
                if (!empty($check)) {
                    return $check;
                }

                if (count($arguments) !== 1) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }
                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $result[] = 'OK ' . $command . '-GRANTED';
                break;
            case 'LOGIN':
                $check = $this->checkAuth();
                if (!empty($check)) {
                    return $check;
                }

                if (count($arguments) !== 1) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }
                if ($this->getFlag(self::UPS_LOGIN) === true) {
                    $this->logger->error('Already logged in');
                    return ['ERR ALREADY-LOGGED-IN'];
                }
                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $this->setFlag(self::UPS_LOGIN, true);
                $result[] = 'OK';
                break;
            case 'LOGOUT':
                if (count($arguments) !== 0) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                $this->setFlag(self::UPS_LOGIN, false);
                $result[] = 'OK Goodbye';
                break;
        }
        return $result;
    }

    private function runMiscCommand(string $command, array $arguments)
    {
        $result = [];
        switch ($command) {
            case 'HELP':
                $result[] = 'Commands: HELP VER GET LIST SET INSTCMD LOGIN LOGOUT USERNAME PASSWORD STARTTLS';
                break;
            case 'VER':
                $result[] = $this->getServerInfo();
                break;
            case 'NETVER':
                if (version_compare($this->serverVersion, '2.6.4', '<')) {
                    return $this->errorAvailableFrom('NETVER', '2.6.4');
                }
                $result[] = $this->getProtocolVersion();
                break;
            case 'PROTVER':
                if (version_compare($this->serverVersion, '2.8.0', '<')) {
                    return $this->errorAvailableFrom('TRACKING', '2.8.0');
                }
                $result[] = $this->getProtocolVersion();
                break;
            case 'INSTCMD': // Instant Command
                $check = $this->checkAuth();
                if (!empty($check)) {
                    return $check;
                }

                $noOfArguments = count($arguments);
                if (
                    $noOfArguments == 3
                    && version_compare($this->serverVersion, '2.8.0', '<')
                ) {
                    $this->logger->error(
                        '"cmdparam" available from v2.8.0. You are running {0}',
                        [
                            $this->serverVersion
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if (version_compare($this->serverVersion, '2.8.0', '>=')) {
                    $availArguments = 3;
                } else {
                    $availArguments = 2;
                }

                if ($noOfArguments <= 1 || $noOfArguments > $availArguments) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => $noOfArguments,
                            ...$arguments
                        ]
                    );
                }

                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR CMD-NOT-SUPPORTED'];
                }

                if (isset($arguments[2]) && $arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR CMD-NOT-SUPPORTED'];
                }

                if ($this->getFlag(self::TRACKING_ENABLED) === true) {
                    $result[] = 'OK TRACKING ' . $this->nutUUIDv4();
                } else {
                    $result[] = 'OK';
                }
                break;
            case 'FSD': // Forced ShutDown
                $check = $this->checkAuth();
                if (!empty($check)) {
                    return $check;
                }

                if (count($arguments) !== 1) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }
                if ($arguments[0] != $this->expectedValue) {
                    $this->logger->error('$arg[0] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                if ($this->getFlag(self::IS_OK) === false) {
                    return ['ERR'];
                }
                $result[] = 'OK FSD-SET';
                break;
            case 'STARTTLS':
                if ($this->getFlag(self::TLS_ENABLED) === true) {
                    $this->logger->error('TLS Already Activated');
                    return ['ERR ALREADY-SSL-MODE'];
                }
                if ($this->expectedValue) {
                    $this->logger->error('Feature not configured');
                    return ['ERR FEATURE-NOT-CONFIGURED'];
                }
                $this->setFlag(self::TLS_ENABLED, true);
                $result[] = 'OK STARTTLS';
                break;
        }
        return $result;
    }

    private function runGetCommand(string $commmand, array $arguments): array
    {
        if (count($arguments) < 1) {
            $this->logger->error(
                'Invalid no. of arguments: {args}',
                [
                    'args' => count($arguments),
                    ...$arguments
                ]
            );
            return ['ERR INVALID-ARGUMENT'];
        }

        $result = [];
        switch ($arguments[0]) {
            case 'NUMLOGINS':
                // Request:  GET NUMLOGINS <upsname>
                // Response: NUMLOGINS <upsname> <value>
                if (count($arguments) < 2) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                $result[] = sprintf('%s %s %d', $arguments[0], $this->expectedValue, $this->expectedDesc);
                break;
            case 'UPSDESC':
                // Request:  GET UPSDESC <upsname>
                // Response: UPSDESC <upsname> "<description>"
                if (count($arguments) < 2) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if (!empty($this->expectedDesc)) {
                    $result[] = sprintf('%s %s "%s"', $arguments[0], $this->expectedValue, $this->expectedDesc);
                } else {
                    $result[] = sprintf('%s %s "%s"', $arguments[0], $this->expectedValue, 'Unavailable');
                }
                break;
            case 'VAR':
                // Request:  GET VAR <upsname> <varname>
                // Response: VAR <upsname> <varname> "<value>"
                if (count($arguments) < 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if (!strncasecmp('server.', $arguments[2], 7)) {
                    if (!strcasecmp('server.info', $arguments[2])) {
                        $result[] = sprintf(
                            '%s %s server.info "%s"',
                            $arguments[0],
                            $arguments[1],
                            $this->getServerInfo()
                        );
                    } elseif (!strcasecmp('server.version', $arguments[2])) {
                        $result[] = sprintf(
                            '%s %s server.version "%s"',
                            $arguments[0],
                            $arguments[1],
                            $this->getServerVersion()
                        );
                    } else {
                        $this->logger->error(
                            'Invalid no. of arguments: {args}',
                            [
                                'args' => count($arguments),
                                ...$arguments
                            ]
                        );
                        $result[] = 'ERR VAR-NOT-SUPPORTED';
                    }
                    break;
                }
                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }
                $result[] = sprintf('%s %s %s "100"', $arguments[0], $arguments[1], $this->expectedValue);
                break;
            case 'TYPE':
                // Request:  GET TYPE <upsname> <varname>
                // Response: TYPE <upsname> <varname> <type>...
                if (count($arguments) != 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }
                /**
                 * RW: this variable may be set to another value with SET
                 * ENUM: an enumerated type, which supports a few specific values
                 * STRING:n: this is a string of maximum length n
                 * RANGE: this is an numeric, either integer or float, comprised in the range (see LIST RANGE)
                 * NUMBER: this is a simple numeric value, either integer or float
                 *
                 * ENUM, STRING and RANGE are usually associated with RW, but not always.
                 * The default <type>, when omitted, is numeric, so either integer or float.
                 * Each driver is then responsible for handling values as either integer or float.
                 */

                $type = 'RW STRING:3';
                $result[] = sprintf('%s %s %s %s', $arguments[0], $arguments[1], $arguments[2], $type);
                break;
            case 'DESC':
                // Request:  GET DESC <upsname> <varname>
                // Response: DESC <upsname> <varname> "<description>"
                if (count($arguments) < 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }
                if (!empty($this->expectedDesc)) {
                    $result[] = sprintf(
                        '%s %s %s "%s"',
                        $arguments[0],
                        $arguments[1],
                        $this->expectedValue,
                        $this->expectedDesc
                    );
                } else {
                    $result[] = sprintf(
                        '%s %s %s "%s"',
                        $arguments[0],
                        $arguments[1],
                        $this->expectedValue,
                        'Description unavailable'
                    );
                }
                break;
            case 'CMDDESC':
                // Request:  GET CMDDESC <upsname> <cmdname>
                // Response: CMDDESC <upsname> <cmdname> "<description>"
                if (count($arguments) < 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }
                if (!empty($this->expectedDesc)) {
                    $result[] = sprintf(
                        '%s %s %s "%s"',
                        $arguments[0],
                        $arguments[1],
                        $this->expectedValue,
                        $this->expectedDesc
                    );
                } else {
                    $result[] = sprintf(
                        '%s %s %s "%s"',
                        $arguments[0],
                        $arguments[1],
                        $this->expectedValue,
                        'Description unavailable'
                    );
                }
                break;
            case 'TRACKING':
                // Request:  GET TRACKING      (activation status of TRACKING)
                //           GET TRACKING <id> (execution status of a command / setvar)
                // Response: <value>
                //  ON                   (TRACKING feature is enabled)
                //  OFF                  (TRACKING feature is disabled)
                //  PENDING              (command execution is pending)
                //  SUCCESS              (command was successfully executed)
                //  ERR UNKNOWN          (command execution failed with unknown error)
                //  ERR INVALID-ARGUMENT (command execution failed due to missing or invalid argument)
                //  ERR FAILED           (command execution failed)
                if (version_compare($this->serverVersion, '2.8.0', '<')) {
                    return $this->errorAvailableFrom('TRACKING', '2.8.0');
                }

                if (empty($arguments[1])) {
                    $result[] = $this->bool2OnOff($this->getFlag(self::TRACKING_ENABLED));
                    break;
                } elseif ($this->getFlag(self::TRACKING_ENABLED)) {
                    if (count($arguments) != 2) {
                        $this->logger->error(
                            'Invalid no. of arguments: {args}',
                            [
                                'args' => count($arguments),
                                ...$arguments
                            ]
                        );
                        return ['ERR INVALID-ARGUMENT'];
                    }
                    $validStatuses = ['PENDING', 'SUCCESS', 'ERR UNKNOWN', 'ERR INVALID-ARGUMENT', 'ERR INVALID'];
                    if (in_array($this->expectedValue, $validStatuses)) {
                        $result[] = $this->expectedValue;
                        break;
                    }
                    $this->logger->error(
                        'Invalid status for: {arg} - {status}',
                        [
                            'arg' => $arguments[1],
                            'status' => $this->expectedValue
                        ]
                    );
                    return ['ERR UNKNOWN'];
                }
                $this->logger->error('Feature not configured', $arguments);
                //$this->showServerInfo();
                $result[] = 'ERR FEATURE-NOT-CONFIGURED';
                break;
            default:
                $this->logger->error('Invalid argument', $arguments);
                $result[] = 'ERR INVALID-ARGUMENT';
        }

        return $result;
    }

    private function runListCommand(string $command, array $arguments): array
    {
        if (count($arguments) < 1) {
            $this->logger->error('Invalid no. of arguments: {args}', ['args' => count($arguments), ...$arguments]);
            return ['ERR INVALID-ARGUMENT'];
        }
        $result = [];
        if (isset($arguments[1])) {
            $listSection = $arguments[1];
        } else {
            $listSection = $this->expectedValue;
        }

        switch ($arguments[0]) {
            case 'UPS':
                // Request:  LIST UPS
                // Response:
                //  BEGIN LIST UPS
                //  UPS <upsname> "<description>"
                //  ...
                //  END LIST UPS
                $result[] = sprintf('%s %s "%s"', $arguments[0], $this->expectedValue, $this->expectedDesc);
                $result[] = sprintf('%s %s "%s"', $arguments[0], 'Test_UPS2', 'Test UPS 2');
                break;
            case 'VAR':
                // Request:  LIST VAR <upsname>
                // Response:
                //  BEGIN LIST VAR <upsname>
                //  VAR <upsname> <varname> "<value>"
                //  ...
                //  END LIST VAR <upsname>
                if (count($arguments) < 2) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    'battery.charge',
                    '100'
                );
                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    'battery.voltage',
                    '14.44'
                );
                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    $this->expectedValue,
                    $this->expectedDesc
                );
                break;
            case 'RW':
                // Request:  LIST RW <upsname>
                // Response:
                //  BEGIN LIST RW <upsname>
                //  RW <upsname> <varname> "<value>"
                //  ...
                //  END LIST RW <upsname>
                if (count($arguments) < 2) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    'output.voltage.nominal',
                    '115'
                );
                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    'ups.delay.shutdown',
                    '020'
                );
                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    $this->expectedValue,
                    $this->expectedDesc
                );
                break;
            case 'CMD':
                // Request:  LIST CMD <upsname>
                // Response:
                //  BEGIN LIST CMD <upsname>
                //  CMD <upsname> <cmdname>
                //  ...
                //  END LIST CMD <cmdname>
                if (count($arguments) < 2) {
                    $this->logger->error('Invalid no. of arguments: {args}', ['args' => count($arguments)]);
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], 'load.on');
                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], 'test.panel.start');
                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], $this->expectedValue);
                break;
            case 'ENUM':
                // Request:  LIST ENUM <upsname> <varname>
                // Response:
                //  BEGIN LIST ENUM <upsname> <varname>
                //  ENUM <upsname> <varname> "<value>"
                //  ...
                //  END LIST ENUM <upsname> <varname>
                if (count($arguments) < 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }

                $listSection .= ' ' . $arguments[2];
                $result[] = sprintf(
                    '%s %s %s "%s"',
                    $arguments[0],
                    $arguments[1],
                    $arguments[2],
                    $this->expectedValue
                );
                break;
            case 'RANGE':
                // Request:  LIST RANGE <upsname> <varname>
                // Response:
                //  BEGIN LIST RANGE <upsname> <varname>
                //  RANGE <upsname> <varname> "<min>" "<max>"
                //  ...
                //  END LIST RANGE <upsname> <varname>
                if (count($arguments) < 3) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }

                $listSection .= ' ' . $arguments[2];
                $result[] = sprintf(
                    '%s %s %s "%s" "%s"',
                    $arguments[0],
                    $arguments[1],
                    $this->expectedValue,
                    $this->expectedDesc,
                    $this->expectedDesc
                );
                //$result[] = sprintf('%s %s %s', $sub, $arguments[0], $this->expectedValue);
                break;
            case 'CLIENT':
                // Request:  LIST CLIENT <device_name>
                // Response:
                //  BEGIN LIST CLIENT <device_name>
                //  CLIENT <device name> <client IP address>
                //  ...
                //  END LIST CLIENT <device_name>
                if (version_compare($this->serverVersion, '2.6.4', '<')) {
                    return $this->errorAvailableFrom('LIST CLIENT', '2.6.4');
                }

                if (count($arguments) < 2) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }

                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], '::1');
                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], '127.0.0.1');
                $result[] = sprintf('%s %s %s', $arguments[0], $arguments[1], $this->expectedValue);
                break;
            default:
                $this->logger->error('Invalid argument: {arg}', ['arg' => $arguments[0]]);
                return ['ERR INVALID-ARGUMENT'];
        }

        return [
            'BEGIN LIST ' . $arguments[0] . ' ' . $listSection,
            ...$result,
            'END LIST ' . $arguments[0] . ' ' . $listSection
        ];
    }

    private function runSetCommand(string $command, array $arguments): array
    {
        $check = $this->checkAuth();
        if (!empty($check)) {
            return $check;
        }

        $result = [];
        switch ($arguments[0]) {
            case 'VAR':
                // Request:  SET VAR <upsname> <varname> "<value>"
                // Response:
                //  OK                         (if TRACKING is not enabled)
                //  OK TRACKING <id>           (if TRACKING is enabled)
                if (count($arguments) < 4) {
                    $this->logger->error(
                        'Invalid no. of arguments: {args}',
                        [
                            'args' => count($arguments),
                            ...$arguments
                        ]
                    );
                    return ['ERR INVALID-ARGUMENT'];
                }

                if ($arguments[1] != $this->expectedValue) {
                    $this->logger->error('$arg[1] != $expectedValue', $arguments);
                    return ['ERR UNKNOWN-UPS'];
                }
                if ($arguments[2] != $this->expectedValue) {
                    $this->logger->error('$arg[2] != $expectedValue', $arguments);
                    return ['ERR VAR-NOT-SUPPORTED'];
                }
                if ($arguments[3] != $this->expectedValue) {
                    $this->logger->error('$arg[3] != $expectedValue', $arguments);
                    return ['ERR INVALID-VALUE'];
                }

                if ($this->getFlag(self::TRACKING_ENABLED) === true) {
                    $result[] = 'OK TRACKING ' . $this->nutUUIDv4();
                } else {
                    $result[] = 'OK';
                }
                break;
            case 'TRACKING':
                // Request:
                //  SET TRACKING <value>
                //  SET TRACKING ON
                //  SET TRACKING OFF
                // Response:
                //  OK
                //  ERR INVALID-ARGUMENT  (if <value> is not "ON" or "OFF")
                //  ERR USERNAME-REQUIRED (if not yet authenticated)
                //  ERR PASSWORD-REQUIRED (if not yet authenticated)
                if (version_compare($this->serverVersion, '2.8.0', '<')) {
                    return $this->errorAvailableFrom('TRACKING', '2.8.0');
                }

                if (in_array($arguments[1], ['ON', 'OFF'])) {
                    $this->logger->info('BEF. FLAGS: {0}, {1}', [$this->bin($this->flags), $arguments[1]]);

                    $this->setFlag(self::TRACKING_ENABLED, $arguments[1] == 'ON');
                    $this->logger->info('AFT. FLAGS: {0}', [$this->bin($this->flags)]);
                    $result[] = 'OK';
                } else {
                    $result[] = 'ERR INVALID-ARGUMENT';
                }
                break;
            default:
                $result[] = 'ERR INVALID-ARGUMENT';
        }

        return $result;
    }
}
