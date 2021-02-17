<?php

namespace Recommerce\Asset\Adapter\Factory;

use Recommerce\Asset\Adapter\SftpClient;

class SftpClientFactory implements AssetFactoryInterface
{
    /**
     * @param array $params
     * @return mixed|SftpClient
     * @throws \Recommerce\Asset\Exception\ConnectionException
     */
    public function create(array $params)
    {
        $host = $params['hostname'];
        $login = $params['username'];
        $password = $params['password'];
        $port = $params['port'];
        $fingerprint = $params['fingerprint'];
        $options = $params['options'];

        return new SftpClient($host, $login, $password, $port, $fingerprint, $options);
    }
}