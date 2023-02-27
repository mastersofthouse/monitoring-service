<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\ExceptionWatcher;

class ExceptionsController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::EXCEPTION;
    }

    protected function watcher(): string
    {
        return ExceptionWatcher::class;
    }
}
