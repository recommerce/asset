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
        $s3client = new \Aws\S3\S3Client([
            'credentials' => [
                'key'    => $params['key'],
                'secret' => $params['secret'],
            ],
            'region'      => $params['region'],
            'version'     => '2006-03-01',
        ]);

        $bucket = $params['bucket'];

        return new S3Client($s3client, $bucket);
    }
}