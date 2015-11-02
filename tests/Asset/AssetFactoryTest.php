<?php

namespace Recommerce\Asset;

use Recommerce\Asset\Adapter\FilesystemClient;
use Zend\ServiceManager\ServiceLocatorInterface;

class AssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetFactory
     */
    private $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serviceManager;

    public function setUp()
    {
        $this->instance = new AssetFactory();
        $this->serviceManager = $this->getMock(ServiceLocatorInterface::class);
    }

    public function testCreateInstance()
    {
        $this
            ->serviceManager
            ->method('has')
            ->will($this->returnValueMap([
                ['Config', true]
            ]));

        $this
            ->serviceManager
            ->method('get')
            ->will($this->returnValueMap([
                [
                    'Config',
                    [
                        'asset' => [
                            'name' => FilesystemClient::class,
                            'args' => [
                                '/tmp'
                            ]

                        ]
                    ]
                ]
            ]));

        $this->assertInstanceOf(
            FilesystemClient::class,
            $this->instance->createService($this->serviceManager)
        );
    }
}
