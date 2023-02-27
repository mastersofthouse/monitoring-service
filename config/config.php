<?php

/*
 * You can place your custom package configuration in here.
 */

use SoftHouse\MonitoringService\Loggly\Loggly;
use SoftHouse\MonitoringService\Watchers\CommandWatcher;
use SoftHouse\MonitoringService\Watchers\EventWatcher;
use SoftHouse\MonitoringService\Watchers\ExceptionWatcher;
use SoftHouse\MonitoringService\Watchers\GateWatcher;
use SoftHouse\MonitoringService\Watchers\JobWatcher;
use SoftHouse\MonitoringService\Watchers\QueueWatcher;
use SoftHouse\MonitoringService\Watchers\RequestWatcher;
use SoftHouse\MonitoringService\Watchers\ScheduleWatcher;

return [
    "enabled" => true,

    'driver' => env('MONITORING_DRIVER', 'database'),

    'extra-info' => null,

    'route' => [
        'prefix' => '',
        'middleware' => [],
    ],

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    "watchers" => [
        CommandWatcher::class => [
            'enabled' => env('MONITORING_COMMAND_WATCHER', true),
            'ignore' => [],
        ],
        EventWatcher::class => [
            'enabled' => env('MONITORING_EVENT_WATCHER', true),
            'ignore' => [],
        ],
        ExceptionWatcher::class => [
            'enabled' => env('MONITORING_EVENT_WATCHER', true),
            'ignore' => [],
        ],
        GateWatcher::class => [
            'enabled' => env('MONITORING_EVENT_WATCHER', true),
            'ignore' => [],
        ],
        JobWatcher::class => env('MONITORING_JOB_WATCHER', true),
        RequestWatcher::class => env('MONITORING_REQUEST_WATCHER', true),
        ScheduleWatcher::class => env('MONITORING_SCHEDULE_WATCHER', true),
        QueueWatcher::class => env('MONITORING_QUEUE_WATCHER', true),
    ],

    'events_watchers' => array(
        CommandWatcher::class => false,
        EventWatcher::class => false,
        ExceptionWatcher::class => false,
        GateWatcher::class => false,
        JobWatcher::class => false,
        RequestWatcher::class => false,
        ScheduleWatcher::class => false,
        QueueWatcher::class => false,
        Loggly::class => false,
    ),

    'events_job' => [
        'pending' => false,
        'processed' => false,
        'failed' => false,
    ],

    'events_loggy' => [
        Loggly::emergency => false,
        Loggly::alert => false,
        Loggly::critical => false,
        Loggly::error => false,
        Loggly::warning => false,
        Loggly::notice => false,
        Loggly::info => false,
        Loggly::model => false,
        Loggly::debug => false,
    ]




];
