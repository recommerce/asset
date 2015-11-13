<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\Exception\ConnectionException;

trait ConnectedClientTrait
{
    /**
     * @var resource
     */
    private $connection;

    /**
     * Maximum connection try number
     *
     * @var int
     */
    private $maxTry;

    /**
     * Interval between each connection try (second)
     *
     * @var int
     */
    private $tryInterval;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $port;

    /**
     * Connect to ftp server with several try
     *
     * @throws ConnectionException
     */
    private function connectWithTry()
    {
        $connection = null;
        $isConnected = false;
        $try = 0;

        $beginTime = new \DateTime();

        while (!$isConnected && ++$try <= $this->maxTry) {
            try {
                $connection = $this->connect();
                $isConnected = true;
            } catch (ConnectionException $e) {
                sleep($this->tryInterval);
            }
        }

        if (!$isConnected) {
            throw new ConnectionException(
                sprintf(
                    "Unable to connect to host for given parameter after %s tries between %s and %s",
                    $try,
                    $beginTime->format('Y-m-d H:i:s'),
                    (new \DateTime())->format('Y-m-d H:i:s')
                )
            );
        }

        return $connection;
    }

    /**
     * Connect to ftp server
     *
     * @return resource
     * @throws ConnectionException
     */
    private function connect()
    {
        $connection = $this->internalConnect();

        if (!$connection) {
            throw new ConnectionException(
                "Unable to connect to host for given parameter"
            );
        }

        return $connection;
    }

    /**
     * @throws ConnectionException
     */
    private function reconnectIfNeeded()
    {
        $this->connection = (!$this->connection || !$this->isConnected())
            ? $this->connect()
            : $this->connection;
    }

    /**
     * @return bool
     */
    private function isConnected()
    {
        return is_array(ftp_nlist($this->connection, "."));
    }
}
