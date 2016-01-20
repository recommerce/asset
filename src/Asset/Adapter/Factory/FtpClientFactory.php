<?php

namespace Recommerce\Asset\Adapter\Factory;

use Recommerce\Asset\Adapter\FtpClient;
use Recommerce\Asset\Adapter\S3Client;

class FtpClientFactory implements AssetFactoryInterface
{
    /**
     * @param array $params
     * @return S3Client
     */
    public function create(array $params)
    {
        $host = $params['hostname'];
        $login = $params['username'];
        $password = $params['password'];
        $port = $params['port'];

        return new FtpClient($host, $login, $password, $port);
    }
}