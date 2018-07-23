<?php

namespace Recommerce\Asset\Adapter\Factory;

use Recommerce\Asset\Adapter\FtpClient;
use Recommerce\Asset\Adapter\S3Client;

class FtpClientFactory implements AssetFactoryInterface
{
    /**
     * @param array $params
     * @return mixed|FtpClient
     * @throws \Recommerce\Asset\Exception\ConnectionException
     */
    public function create(array $params)
    {
        $host = $params['hostname'];
        $login = $params['username'];
        $password = $params['password'];
        $port = $params['port'];
        $useFtps = $params['useFtps'];

        return new FtpClient($host, $login, $password, $port, $useFtps);
    }
}