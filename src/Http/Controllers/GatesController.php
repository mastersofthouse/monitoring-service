<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\GateWatcher;

class GatesController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::GATE;
    }

    protected function watcher(): string
    {
        return GateWatcher::class;
    }
}
