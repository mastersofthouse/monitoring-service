<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use SoftHouse\MonitoringService\Events\MonitoringScheduleEvent;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;

class ScheduleWatcher extends Watcher
{

    public function register($app)
    {
        $app['events']->listen(CommandStarting::class, [$this, 'recordCommand']);
    }

    public function recordCommand(CommandStarting $event)
    {
        if(!MonitoringService::isRecording()){
            return;
        }

        if($event->command !== 'schedule:run' && $event->command !== 'schedule:finish'){
            return;
        }

        collect(app(Schedule::class)->events())->each(function ($event) {
            $event->then(function () use ($event) {
                $entry = IncomingEntry::make([
                    'command' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
                    'description' => $event->description,
                    'expression' => $event->expression,
                    'timezone' => $event->timezone,
                    'user' => $event->user,
                    'output' => $this->getEventOutput($event),
                ]);

                MonitoringService::recordScheduledCommand($entry);

                if(MonitoringService::isEnabledNotification(self::class)){
                    event(new MonitoringScheduleEvent($entry));
                }
            });
        });
    }

    protected function getEventOutput(Event $event)
    {
        if (! $event->output ||
            $event->output === $event->getDefaultOutput() ||
            $event->shouldAppendOutput ||
            ! file_exists($event->output)) {
            return '';
        }

        return trim(file_get_contents($event->output));
    }
}
