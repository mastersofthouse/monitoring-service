<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Console\Events\CommandFinished;
use SoftHouse\MonitoringService\Events\MonitoringCommandEvent;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;

class CommandWatcher extends Watcher
{
    public function register($app)
    {
        $app['events']->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    public function recordCommand(CommandFinished $event){

        if(!MonitoringService::isRecording()) return;

        if($this->shouldIgnore($event)) return;

        $entry = IncomingEntry::make([
            'command' => $event->command ?? $event->input->getArguments()['command'] ?? 'default',
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
        ]);

        MonitoringService::recordCommand($entry);

        if(MonitoringService::isEnabledNotification(self::class)){
            event(new MonitoringCommandEvent($entry));
        }
    }

    private function shouldIgnore($event): bool
    {
        return in_array($event->command, array_merge($this->options['ignore'] ?? [], [
            'schedule:run',
            'schedule:finish',
            'package:discover',
        ]));
    }
}
