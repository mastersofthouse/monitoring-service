<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SoftHouse\MonitoringService\Skeleton\SkeletonClass
 */
class MonitoringServiceFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'monitoring-service';
    }
}
