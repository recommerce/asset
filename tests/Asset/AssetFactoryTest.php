<?php

namespace Recommerce\Asset;

use Interop\Container\ContainerInterface;
use Recommerce\Asset\Adapter\Factory\FilesystemClientFactory;
use Recommerce\Asset\Adapter\FilesystemClient;

class AssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetFactory
     */
    private $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    public function setUp()
    {
        $this->instance = new AssetFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testCreateInstance()
    {
        $this
            ->container
            ->method('has')
            ->will($this->returnValueMap([
                ['Config', true]
            ]));

        $this
            ->container
            ->method('get')
            ->will($this->returnValueMap([
                [
                    'Config',
                    [
                        'asset' => [
                            'factory' => FilesystemClientFactory::class,
                            'params' => [
                                'repository' => '/tmp'
                            ]

                        ]
                    ]
                ]
            ]));

        $this->assertInstanceOf(
            FilesystemClient::class,
            $this->instance->__invoke($this->container, 'name')
        );
    }
}
