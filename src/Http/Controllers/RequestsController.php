<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\RequestWatcher;

class RequestsController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::REQUEST;
    }

    protected function watcher(): string
    {
        return RequestWatcher::class;
    }
}
