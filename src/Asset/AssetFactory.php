<?php

namespace Recommerce\Asset;

use Interop\Container\ContainerInterface;
use Recommerce\Asset\Exception\InvalidConfigurationException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class AssetFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$container->has('Config')) {
            throw new ServiceNotFoundException("No Config service has been registered.");
        }

        return $this->createServiceFromConfig($container->get('Config')['asset']);
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
