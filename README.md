[![Build Status](https://travis-ci.org/recommerce/asset.svg?branch=master)](https://travis-ci.org/recommerce/asset) [![Code Climate](https://codeclimate.com/github/recommerce/asset/badges/gpa.svg)](https://codeclimate.com/github/recommerce/asset) [![Test Coverage](https://codeclimate.com/github/recommerce/asset/badges/coverage.svg)](https://codeclimate.com/github/recommerce/asset/coverage)

# Recommerce asset

This library provides an interface and some implementation to handle files on assets.

Current implementations are :
* AWS S3 : Amazon S3 service (using AWS S3 SDK library) ;
* FileSystem : Use your local file system as asset ;
* FTP : FTP server (not SFTP).

## Installation with composer

```sh
composer require recommerce/asset:2.0.*
composer update
```

## Usage examples

### AWS S3 client creation
```php
    use Recommerce\Asset\AssetFactory;
    use Recommerce\Asset\Adapter\Factory\S3ClientFactory;
    use Zend\ServiceManager\ServiceManager;

    $config = [
        'asset' => [
            'factory' => S3ClientFactory::class,
            'params' => [
                'key'    => 'YOUR_S3_KEY',
                'secret' => 'YOUR_S3_SECRET',
                'region' => 'YOUR_AWS_REGION',
                'bucket' => 'YOUR_S3_BUCKET_NAME'
            ]
        ],
    ];

    $serviceManager = new ServiceManager();
    $serviceManager->setService('Config', $config);

    $assetClient = (new AssetFactory())->createService($serviceManager);
```
### Filesystem client creation
```php
    use Recommerce\Asset\AssetFactory;
    use Recommerce\Asset\Adapter\Factory\FileSystemClientFactory;
    use Zend\ServiceManager\ServiceManager;

    $config = [
        'asset' => [
            'factory' => FileSystemClientFactory::class,
            'params' => [
                'repository' => 'YOUR_LOCAL_ASSET_REPOSITORY'
            ]
        ],
    ];

    $serviceManager = new ServiceManager();
    $serviceManager->setService('Config', $config);

    $assetClient = (new AssetFactory())->createService($serviceManager);
```

### FTP client creation
```php
    use Recommerce\Asset\AssetFactory;
    use Recommerce\Asset\Adapter\Factory\FtpClientFactory;
    use Zend\ServiceManager\ServiceManager;

    $config = [
        'asset' => [
            'factory' => FtpClientFactory::class,
            'params' => [
                'hostname' => 'YOUR_HOST',
                'username' => 'YOUR_USERNAME',
                'password' => 'YOUR_PASSWORD',
                'port' => 'YOUR_PORT' // 21 by default,
            ]
        ],
    ];

    $serviceManager = new ServiceManager();
    $serviceManager->setService('Config', $config);

    $assetClient = (new AssetFactory())->createService($serviceManager);
```

### Basics
```php
// Get asset file on local file system
$assetClient->get('path/to/remote_file.png', '/tmp/my_local_file.png');

// Put local file on asset
$assetClient->put('/tmp/my_local_file.png', 'path/to/remote_file.png');

// Check file existence on asset
$assetClient->exists('path/to/remote_file.png');

// Remove asset file
$assetClient->remove('path/to/remote_file.png');
```
