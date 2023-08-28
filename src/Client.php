<?php

namespace TXC\NUT;

use InvalidArgumentException;
use TXC\NUT\Exception\NutException;
use TXC\NUT\Exception;

class Client extends AbstractClient
{
    private function parseResponse(string $buffer, ?int $offset = null, ?int $length = null): array
    {
        $arguments = explode(' ', trim($buffer));
        $result = [];
        $pos = 0;
        $inString = false;
        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '"')) {
                $inString = !$inString;
                $argument = substr($argument, 1);
            }
            if (str_ends_with($argument, '"')) {
                $inString = !$inString;
                $argument = substr($argument, 0, -1);
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

    /**
     * VAR <upsname> <varname> "<value>"
     * RW <upsname> <varname> "<value>"
     * ENUM <upsname> <varname> "<value>"
     * RANGE <upsname> <varname> "<min>" "<max>"
     *
     * # UPS <upsname> "<description>"
     * # CMD <upsname> <cmdname>
     * # CLIENT <device name> <client IP address>
     */
    private function parseListResponse(array $list): array
    {
        $results = [];

        $varname = $list[0];
        if (count($list) < 2) {
            return [$varname];
        }
        for ($i = 1; $i < count($list); $i++) {
            if (
                isset($results[$varname])
                && !is_array($results[$varname])
            ) {
                $results[$varname] = [$results[$varname], $list[$i]];
            /*
            } elseif (
                isset($results[$varname])
                && is_array($results[$varname])
                ) {
                    $results[$varname][] = $list[$i];
            */
            } else {
                $results[$varname] = $list[$i];
            }
        }
        return $results;
    }

    /**
     * Method for handle common logic on LIST operations
     */
    protected function listOperation(string $operation, ...$args): array
    {
        array_unshift($args, 'LIST', $operation);
        $result = $this->sendCommand(...$args);

        $this->logger->info(sprintf('OUTPUT : LIST ' . $operation, ...$args), $result);

        $firstLineExpected = sprintf('BEGIN LIST ' . $operation, ...$args);
        $lastLineExpected = sprintf('END LIST ' . $operation, ...$args);
        $firstLine = array_shift($result);
        $lastLine = array_pop($result);
        if (!str_starts_with($firstLine, $firstLineExpected)) {
            $this->logger->error(
                'Response mismatch',
                [
                    'expected' => $firstLineExpected,
                    'actual' => $firstLine
                ]
            );
            throw new NutException('Invalid response: ' . $firstLine);
        }
        if (!str_starts_with($lastLine, $lastLineExpected)) {
            $this->logger->error(
                'Response mismatch',
                [
                    'expected' => $lastLineExpected,
                    'actual' => $lastLine
                ]
            );
            throw new NutException('Invalid response: ' . $lastLine);
        }

        $this->logger->info('{operation} Results', ['operation' => $operation, 'result' => $result]);
        $results = [];
        foreach ($result as $res) {
            if (!str_starts_with($res, $operation)) {
                throw new NutException('Invalid response: ' . $res);
            }
            // Atleast 3 sections
            // BEGIN <command> <subcommand> <param> [...<params>]
            // <command> <upsname> <value> [...<value>]
            // ...
            // END <command> <subcommand> <param> [...<params>]
            $list = $this->parseResponse($res);

            array_shift($list);
            $ups = array_shift($list);

            if (count($list) > 1) {
                if (isset($results[$ups])) {
                    $results[$ups] = array_merge_recursive(
                        $results[$ups],
                        $this->parseListResponse($list),
                    );
                } else {
                    $results[$ups] = $this->parseListResponse($list);
                }
            } else {
                if (isset($results[$ups])) {
                    $results[$ups] = array_merge_recursive(
                        $results[$ups],
                        $this->parseListResponse($list),
                    );
                } else {
                    $results[$ups] = $this->parseListResponse($list);
                }
            }
        }

        return $results;
    }

    /**
     * Method for handle common logic on GET operations
     */
    protected function getOperation(string $operation, ...$args): string
    {
        array_unshift($args, 'GET', $operation);
        $result = $this->sendCommand(...$args);
        $result = current($result);
        if (!str_starts_with($result, $operation)) {
            throw new NutException('Invalid response: ' . $result);
        }
        $list = $this->parseResponse($result);

        $paramCount = count($args) - 1;
        $list = array_slice($list, $paramCount);

        if (count($list) > 1) {
            return implode(' ', $list);
        }
        return current($list);
    }

    public function sendCommand(...$args): array
    {
        $argCount = count($args);
        if ($argCount < 1) {
            throw new InvalidArgumentException();
        }
        $command = str_repeat('%s ', $argCount);
        $command = trim($command);

        $this->logger->debug($command . " called...", $args);
        $this->logger->info(sprintf('INPUT  : ' . $command, ...$args));

        $result = $this->getHandler()->send($command . "\n", ...$args);

        if (
            count($result) === 1
            && str_starts_with($result[0], 'ERR')
        ) {
            $response = explode(' ', $result[0]);
            switch ($response[1] ?? 'UNKNOWN') {
                case 'ACCESS-DENIED':
                    throw new Exception\AccessDenied();
                case 'ALREADY-LOGGED-IN':
                    throw new Exception\AlreadyLoggedIn();
                case 'ALREADY-SSL-MODE':
                    throw new Exception\AlreadySSLMode();
                case 'ALREADY-SET-PASSWORD':
                    throw new Exception\AlreadySetPassword();
                case 'ALREADY-SET-USERNAME':
                    throw new Exception\AlreadySetUsername();
                case 'CMD-NOT-SUPPORTED':
                    throw new Exception\CmdNotSupported();
                case 'DATA-STALE':
                    throw new Exception\DataStale();
                case 'DRIVER-NOT-CONNECTED':
                    throw new Exception\DriverNotConnected();
                case 'FEATURE-NOT-CONFIGURED':
                    throw new Exception\FeatureNotConfigured();
                case 'FEATURE-NOT-SUPPORTED':
                    throw new Exception\FeatureNotSupported();
                case 'INSTCMD-FAILED':
                    throw new Exception\InstcmdFailed();
                case 'INVALID-ARGUMENT':
                    throw new Exception\InvalidArgument();
                case 'INVALID-PASSWORD':
                    throw new Exception\InvalidPassword();
                case 'INVALID-USERNAME':
                    throw new Exception\InvalidUsername();
                case 'INVALID-VALUE':
                    throw new Exception\InvalidValue();
                case 'PASSWORD-REQUIRED':
                    throw new Exception\PasswordRequired();
                case 'READONLY':
                    throw new Exception\VarIsReadOnly();
                case 'SET-FAILED':
                    throw new Exception\SetFailed();
                case 'TOO-LONG':
                    throw new Exception\TooLong();
                case 'UNKNOWN-COMMAND':
                    throw new Exception\UnknownCommand();
                case 'UNKNOWN-UPS':
                    throw new Exception\UnknownUps();
                case 'USERNAME-REQUIRED':
                    throw new Exception\UsernameRequired();
                case 'VAR-NOT-SUPPORTED':
                    throw new Exception\VarNotSupported();
                default:
                    throw new Exception\NutException('Unknown error: ' . $result[0]);
            }
        }

        if (
            strtoupper($args[0]) != 'LIST'
            && is_array($result)
            && count($result) !== 1
        ) {
            $this->logger->error(
                'Invalid response, expected single row, got {rows} rows',
                [
                    'rows' => count($result),
                    'result' => $result
                ]
            );
        }

        return $result;
    }

    //region LIST Operations
    /**
     * Returns the list of available UPS from the NUT server.
     *
     * The result is a dictionary containing 'key->val' pairs of
     * 'UPSName' and 'UPS Description'.
     */
    public function listUPS(): array
    {
        $res = $this->listOperation('UPS');
        $result = [];
        foreach ($res as $ups => $value) {
            $result[$ups] = current($value);
        }
        return $result;
    }

    /**
     * Get all available vars from the specified UPS.
     *
     * The result is a dictionary containing 'key->val' pairs of all
     * available vars.
     */
    public function listVars(string $ups): array
    {
        return $this->listOperation('VAR', $ups);
    }

    /**
     * Get all available commands for the specified UPS.
     *
     * The result is a dict object with command name as key and a description
     * of the command as value.
     */
    public function listCommands(string $ups): array
    {
        $commands = $this->listOperation('CMD', $ups);
        if (!isset($commands[$ups])) {
            return [];
        }

        $result = [$ups => []];
        foreach ($commands[$ups] as $command) {
            // For each var we try to get the available description
            try {
                $res = $this->getCommandDescription($ups, $command);
                $result[$ups][$command] = $res;
            } catch (NutException $e) {
                $result[$ups][$command] = $command;
            }
        }
        return $result;
    }

    /**
     * Returns the list of connected clients from the NUT server.
     *
     * The result is a dictionary containing 'key->val' pairs of
     * 'UPSName' and a list of clients.
     */
    public function listClients(string $ups): array
    {
        return $this->listOperation('CLIENT', $ups);
    }

    /**
     * Get a list of all writable vars from the selected UPS.
     *
     * The result is presented as a dictionary containing 'key->val'
     * pairs.
     */
    public function listRWVars(string $ups): array
    {
        return $this->listOperation('RW', $ups);
    }

    /**
     * Get a list of valid values for an enum variable.
     *
     * The result is presented as a list.
     */
    public function listEnum(string $ups, string $var): array
    {
        return $this->listOperation('ENUM', $ups, $var);
    }

    /**
     * Get a list of valid values for a range variable.
     *
     * The result is presented as a list.
     */
    public function listRange(string $ups, string $var): array
    {
        $result = $this->listOperation('RANGE', $ups, $var);
        $results = [];
        foreach ($result[$ups] as $key => $res) {
            $results[$key] = [
                'min' => $res[0],
                'max' => $res[1],
            ];
        }
        return $results;
    }
    //endregion LIST Operations

    //region SET Operations
    /**
     * Set a variable to the specified value on selected UPS.
     *
     * Requires Active Login
     *
     * The variable must be a writable value (cf list_rw_vars) and you
     * must have the proper rights to set it (maybe login/password).
     */
    public function setVar(string $ups, string $var, string $value): string
    {
        //$this->logger->debug(sprintf("SET VAR '%s' %s = %s ...", $ups, $var, $value));
        //$result = $this->getHandler()->send("SET VAR %s %s %s\n", $ups, $var, $value);
        $result = $this->sendCommand('SET', 'VAR', $ups, $var, $value);
        return current($result);
    }

    /**
     * Enable / Disable tracking
     */
    public function setTracking(bool $status): string
    {
        $stringStatus = $status ? 'ON' : 'OFF';

        $this->logger->info('SET TRACKING {0} / {1}', [$stringStatus, (int) $status]);

        $result = $this->sendCommand('SET', 'TRACKING', $stringStatus);
        return current($result);
    }
    //endregion SET Operations

    //region GET Operations
    /**
     * Get the value of a variable.
     */
    public function getVar(string $ups, string $var): string
    {
        return $this->getOperation('VAR', $ups, $var);
    }

    /**
     * Returns the description for a given UPS.
     */
    public function description(string $ups): string
    {
        return $this->getOperation('UPSDESC', $ups);
    }

    /**
     * Get tracking status
     */
    public function getTracking(string $id = ''): string
    {
        $args = [
            'GET',
            'TRACKING'
        ];
        if (!empty($id)) {
            $args[] = $id;
        }

        $result = $this->sendCommand(...$args);
        //$result = current($result);

        $result = $this->parseResponse(...$result);
        return current($result);
    }

    /**
     * Get a variable's description.
     */
    public function varDescription(string $ups, string $var): string
    {
        return $this->getOperation('DESC', $ups, $var);
    }

    /**
     * Get a variable's type.
     */
    public function varType(string $ups, string $var): string
    {
        return $this->getOperation('TYPE', $ups, $var);
    }

    /**
     * Get a command's description.
     */
    public function getCommandDescription(string $ups, string $command): string
    {
        return $this->getOperation('CMDDESC', $ups, $command);
    }

    /**
     * Send GET NUMLOGINS command to get the number of users logged
     * into a given UPS.
     */
    public function getNumLogins(string $ups): int
    {
        return (int) $this->getOperation('NUMLOGINS', $ups);
    }
    //endregion GET Operations

    //region Aliased Operations
    /**
     * Get the value of a variable (alias for get_var).
     * @deprecated
     * @see self::getVar()
     */
    public function get(string $ups, string $var): string
    {
        return $this->getVar($ups, $var);
    }

    /**
     * Get a command's description.
     * (alias for getCommandDescription)
     * @deprecated
     * @see self::getCommandDescription()
     */
    public function commandDescription(string $ups, string $command): string
    {
        return $this->getCommandDescription($ups, $command);
    }

    /**
     * Get the number of users logged into a given UPS.
     * (alias for getNumLogins)
     * @deprecated
     * @see self::getNumLogins()
     */
    public function numLogins(string $ups): int
    {
        return $this->getNumLogins($ups);
    }
    //endregion Aliased Operations

    //region AUTH Operations
    /**
     * Send USERNAME command.
     */
    public function username(string $username): void
    {
        $this->sendCommand('USERNAME', $username);
    }

    /**
     * Send PASSWORD command.
     */
    public function password(string $password): void
    {
        $this->sendCommand('PASSWORD', $password);
    }

    /**
     * Send LOGIN command.
     */
    public function login(string $ups): string
    {
        $result = $this->sendCommand('LOGIN', $ups);
        return current($result);
    }

    /**
     * Send LOGOUT command.
     */
    public function logout(): string
    {
        $result = $this->sendCommand('LOGOUT');
        return current($result);
    }

    /**
     * Send MASTER command.
     * Requires Active Login
     */
    public function master(string $ups): string
    {
        $result = $this->sendCommand('MASTER', $ups);
        return current($result);
    }

    /**
     * Send PRIMARY command.
     * Requires Active Login
     * @since NUT 2.8.0
     * Alias of MASTER command
     */
    public function primary(string $ups): string
    {
        $result = $this->sendCommand('PRIMARY', $ups);
        return current($result);
    }
    //endregion AUTH Operations

    //region MISC Operations
    /**
     * Send a command to the specified UPS.
     * Requires Active Login
     */
    public function instcmd(string $ups, string $command, ?string $cmdparam = null): string
    {
        $cmd = [
            'INSTCMD',
            $ups,
            $command
        ];
        if (!empty($cmdparam)) {
            $cmd[] = $cmdparam;
        }
        $result = $this->sendCommand(...$cmd);
        return current($result);
    }

    /**
     * Send MASTER and FSD commands.
     * Requires Active Login
     */
    public function fsd(string $ups): string
    {
        $currentVersion = $this->version();
        if (version_compare($currentVersion, '2.8.0', '<')) {
            $this->master($ups);
        } else {
            $this->primary($ups);
        }

        $result = $this->sendCommand('FSD', $ups);
        return current($result);
    }

    /**
     * Send HELP command.
     */
    public function help(): string
    {
        $result = $this->sendCommand('HELP');
        return current($result);
    }

    /**
     * Send PROTVER command.
     * Alias of NETVER command
     * @since 2.8.0
     */
    public function protver(): string
    {
        $result = $this->sendCommand('PROTVER');
        return current($result);
    }

    /**
     * Send NETVER command.
     * @since 2.6.4
     */
    public function netver(): string
    {
        $result = $this->sendCommand('NETVER');
        return current($result);
    }

    /**
     * Send VER command.
     */
    public function ver(): string
    {
        $result = $this->sendCommand('VER');
        return current($result);
    }

    /**
     * Returns the version string only
     */
    public function version(): string
    {
        $pattern = '((?>\d+)(?>\.(?>\d+))?(?>\.(?>\d+))?(?>[-_+,](?>\w+))?)';
        if (preg_match('#' . $pattern . '#', $this->ver(), $match)) {
            return $match[0];
        }
        throw new NutException('Unable to find version number');
    }
    //endregion MISC Operations
}
