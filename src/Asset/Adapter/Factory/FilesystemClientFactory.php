<?php

namespace Recommerce\Asset\Adapter\Factory;

use Recommerce\Asset\Adapter\FilesystemClient;
use Recommerce\Asset\Adapter\S3Client;

class FilesystemClientFactory implements AssetFactoryInterface
{
    /**
     * @param array $params
     * @return S3Client
     */
    public function create(array $params)
    {
        $repository = $params['repository'];

        return new FilesystemClient($repository);
    }
}