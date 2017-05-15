<?php

namespace Recommerce\Asset\Adapter\Factory;

use Recommerce\Asset\Adapter\S3Client;

class S3ClientFactory implements AssetFactoryInterface
{
    /**
     * @param array $params
     * @return S3Client
     */
    public function create(array $params)
    {
        $s3client = \Aws\S3\S3Client::factory([
            'key' => $params['key'],
            'secret' => $params['secret']
        ]);
        $bucket = $params['bucket'];

        return new S3Client($s3client, $bucket, $params);
    }
}