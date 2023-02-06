<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\JobWatcher;

class JobsController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::JOB;
    }

    protected function watcher(): string
    {
        return JobWatcher::class;
    }
}
