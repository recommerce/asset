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
#### Direct config
```php
    use Recommerce\Asset\Adapter\Factory\S3ClientFactory;
    use Recommerce\Asset\AssetFactory;

    $config = [
        'factory' => S3ClientFactory::class,
        'params' => [
            AwsS3Client::factory([
                'key'    => 'YOUR_S3_KEY',
                'secret' => 'YOUR_S3_SECRET'
            ]),
            'YOUR_S3_BUCKET_NAME'
        ]
    ];

    $assetClient = (new AssetFactory())->createServiceFromConfig($config);
```

#### Service manager
```php
    use Recommerce\Asset\Adapter\Factory\S3ClientFactory;
    use Recommerce\Asset\AssetFactory;
    use Zend\ServiceManager\Config;
    use Zend\ServiceManager\ServiceManager;

    $config = [
        'asset' => [
            'factory' => S3ClientFactory::class,
            'params' => [
                AwsS3Client::factory([
                    'key'    => 'YOUR_S3_KEY',
                    'secret' => 'YOUR_S3_SECRET'
                ]),
                'YOUR_S3_BUCKET_NAME'
            ]
        ],
        'service_manager' => [
            'factories' => [
                'recommerce.asset.asset-client' => AssetFactory::class
            ]
        ]
    ];

    $serviceManager = new ServiceManager(new Config($config['service_manager']));
    $serviceManager->setService('Config', $config);

    $assetClient = $serviceManager->get('recommerce.asset.asset-client');
```

### Filesystem client creation
#### Direct config
```php
    use Recommerce\Asset\AssetFactory;
    use Recommerce\Asset\Adapter\Factory\FileSystemClientFactory;

    $config = [
        'factory' => FileSystemClientFactory::class,
        'params' => [
            'repository' => 'YOUR_LOCAL_ASSET_REPOSITORY'
        ]
    ];

    $assetClient = (new AssetFactory())->createServiceFromConfig($config);
```

### FTP client creation
#### Direct config
```php
    use Recommerce\Asset\AssetFactory;
    use Recommerce\Asset\Adapter\Factory\FtpClientFactory;

    $config = [
        'factory' => FtpClientFactory::class,
        'params' => [
            'hostname' => 'YOUR_HOST',
            'username' => 'YOUR_USERNAME',
            'password' => 'YOUR_PASSWORD',
            'port' => 'YOUR_PORT' // 21 by default,
        ]
    ];

    $assetClient = (new AssetFactory())->createServiceFromConfig($config);
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