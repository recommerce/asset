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
        $s3clientParams = [
            'key' => $params['key'],
            'secret' => $params['secret']
        ];
        if (isset($params['token'])) {
            $s3clientParams['token'] = $params['token'];
        }

        $s3client = \Aws\S3\S3Client::factory($s3clientParams);
        $bucket = $params['bucket'];

        return new S3Client($s3client, $bucket, $params);
    }
}
