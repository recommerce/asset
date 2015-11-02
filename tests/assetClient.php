<?php

namespace Recommerce;

require_once __DIR__ . '/bootstrap.php';

use Recommerce\Asset\Adapter\SftpClient;
use Recommerce\Asset\Adapter\SftpSecLib;
use Recommerce\Asset\AssetFactory;
use Recommerce\Asset\Adapter\FtpClient;
use Recommerce\Asset\Adapter\S3Client;
use Aws\S3\S3Client as AwsS3Client;
use Zend\ServiceManager\ServiceManager;

$config = [
    // S3 configuration
//    'asset' => [
//        'name' => S3Client::class,
//        'args' => [
//            AwsS3Client::factory([
//                'key'    => 'AKIAI6MFFKQZDHE6HBRQ',
//                'secret' => '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'
//            ]),
//            'mxtbuckettest'
//        ]
//    ],
    // Ftp configuration
//    'asset' => [
//        'name' => FtpClient::class,
//        'args' => [
//            'hostname' => 'ftp.comparecycle.com',
//            'username' => 'sfr-recommerce_test',
//            'password' => 'Ewai9vie'
//        ]
//    ],
    'asset' => [
        'name' => FtpClient::class,
        'args' => [
            'hostname' => '5.2.251.209',
            'username' => 'RS_test',
            'password' => 'TestPass2015',
            'port' => '991'
        ]
    ],
    // Sftp configuration
//    'asset' => [
//        'name' => SftpClient::class,
//        'args' => [
//            'hostname' => '5.2.251.209',
//            'username' => 'RS_test',
//            'password' => 'TestPass2015',
//            'port' => 990, // 991
//        ]
//    ],
    // Sftp seclib configuration
//    'asset' => [
//        'name' => SftpSecLib::class,
//        'args' => [
//            'hostname' => '5.2.251.209',
//            'username' => 'RS_test',
//            'password' => 'TestPass2015',
//            'port' => 990, // 991
//        ]
//    ],
];

$serviceManager = new ServiceManager();
$serviceManager->setService('Config', $config);

$assetClient = (new AssetFactory())->createService($serviceManager);

// AWS S3 tests
//var_dump($assetClient->getFiles('argus'));
//var_dump($assetClient->get('argus/buyers/afone mobile_logo.png', '/tmp/afone mobile_logo.png'));
//var_dump($assetClient->put('/tmp/afone mobile_logo.png', 'argus/afone mobile_logo.png'));
//var_dump($assetClient->exists('argus/afone mobile_logo.png'));
//var_dump($assetClient->remove('argus/afone mobile_logo.png'));

// FTP tests
var_dump($assetClient->listFiles('RS', 'expected'));
//var_dump($assetClient->get('in/IN_48_20150528_162554_8460.csv', '/tmp/test.csv'));
//var_dump($assetClient->put('/tmp/test.csv', 'test.csv'));
//var_dump($assetClient->exists('test.csv'));
//var_dump($assetClient->remove('test.csv'));

// SFTP tests
//var_dump($assetClient->getFiles(''));
//var_dump($assetClient->get('in/IN_48_20150528_162554_8460.csv', '/tmp/test.csv'));
//var_dump($assetClient->put('/tmp/test.csv', 'test.csv'));
//var_dump($assetClient->exists('test.csv'));
//var_dump($assetClient->remove('test.csv'));