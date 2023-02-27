<?php

namespace SoftHouse\MonitoringService\Http\Controllers;
use SoftHouse\MonitoringService\EntryController;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\Watchers\CommandWatcher;

class CommandsController extends EntryController
{

    protected function entryType(): string
    {
        return EntryType::COMMAND;
    }

    protected function watcher(): string
    {
        return CommandWatcher::class;
    }
}
