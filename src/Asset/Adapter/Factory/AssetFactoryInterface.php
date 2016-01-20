<?php

namespace Recommerce\Asset\Adapter\Factory;

interface AssetFactoryInterface
{
    /**
     * @param array $params
     * @return mixed
     */
    public function create(array $params);
}