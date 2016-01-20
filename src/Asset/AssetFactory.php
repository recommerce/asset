<?php

namespace Recommerce\Asset;

use Recommerce\Asset\Exception\InvalidConfigurationException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AssetFactory implements FactoryInterface
{

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @return mixed
     * @throws \Exception
     */
    public function createService(ServiceLocatorInterface $serviceManager)
    {
        if (!$serviceManager->has('Config')) {
            throw new ServiceNotFoundException("No Config service has been registered.");
        }

        return $this->createServiceFromConfig($serviceManager->get('Config')['asset']);
    }

    /**
     * @param array $config
     * @return object
     * @throws InvalidConfigurationException
     */
    public function createServiceFromConfig(array $config)
    {
        if (empty($config['factory'])) {
            throw new InvalidConfigurationException("Asset configuration was not found.");
        }

        $class = $config['factory'];

        if (!class_exists($class)) {
            throw new InvalidConfigurationException("Unknown asset factory");
        }

        return (new $class)->create($config['params']);
    }
}
