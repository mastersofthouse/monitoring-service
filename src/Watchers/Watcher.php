<?php

namespace SoftHouse\MonitoringService\Watchers;

abstract class Watcher
{

    public array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    abstract public function register($app);
}
