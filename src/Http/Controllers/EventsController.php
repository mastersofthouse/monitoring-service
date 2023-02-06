<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\EventWatcher;

class EventsController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::EVENT;
    }

    protected function watcher(): string
    {
        return EventWatcher::class;
    }
}
