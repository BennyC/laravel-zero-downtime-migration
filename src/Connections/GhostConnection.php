<?php

namespace Daursu\ZeroDowntimeMigration\Connections;

use InvalidArgumentException;

class GhostConnection extends BaseConnection
{
    /**
     * Executes the SQL statement through pt-online-schema-change.
     *
     * @param  string $query
     * @param  array  $bindings
     * @return bool|int
     */
    public function statement($query, $bindings = [])
    {
        $table = $this->extractTableFromQuery($query);

        return $this->runProcess(array_merge(
            [
                'gh-ost',
            ],
            $this->getAdditionalParameters(),
            $this->getInteractiveParameters(),
            [
                sprintf('--user=%s', $this->getConfig('username')),
                sprintf('--password=%s', $this->getConfig('password')),
                sprintf('--host=%s', $this->getConfig('host')),
                sprintf('--port=%d', $this->getConfig('port')),
                sprintf('--database=%s', $this->getConfig('database')),
                sprintf('--table=%s', $table),
                sprintf('--alter=%s', $this->cleanQuery($query)),
                $this->isPretending() ? '' : '--execute',
            ]
        ));
    }

    /**
     * Hide the username/pw from console output.
     *
     * @param array $command
     * @return string
     */
    protected function maskSensitiveInformation(array $command): string
    {
        return collect($command)->map(function ($config) {
            $config = preg_replace('/('.preg_quote($this->getConfig('password'), '/').')/', '*****', $config);

            return preg_replace('/('.preg_quote($this->getConfig('username'), '/').')/', '*****', $config);
        })->implode(' ');
    }

    /**
     * Return parameters to decide where we want Commands to run against
     *
     * @return array
     */
    protected function getInteractiveParameters(): array
    {
        switch (config('zero-down.ghost.via')) {
            case 'socket':
                return [
                    '--serve-socket-file' => config('zero-down.ghost.resource'),
                ];
            case 'tcp':
                return [
                    '--serve-tcp-port' => config('zero-down.ghost.resource'),
                ];
            default:
                throw new InvalidArgumentException('Unsupported "via" option provided');
        }
    }
}
