<?php

declare(strict_types=1);

if (!function_exists('loggly')) {
    function loggly(): SoftHouse\MonitoringService\Loggly\Loggly
    {
        return app(\SoftHouse\MonitoringService\Loggly\Loggly::class);
    }
}

if (!function_exists('monitoring')) {
    function monitoring(): \SoftHouse\MonitoringService\MonitoringEntriesRepository
    {
        return app(\SoftHouse\MonitoringService\MonitoringEntriesRepository::class);
    }
}
