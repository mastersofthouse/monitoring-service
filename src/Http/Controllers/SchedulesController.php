<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\ScheduleWatcher;

class SchedulesController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::SCHEDULED_TASK;
    }

    protected function watcher(): string
    {
        return ScheduleWatcher::class;
    }
}
