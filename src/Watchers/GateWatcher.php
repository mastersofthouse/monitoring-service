<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SoftHouse\MonitoringService\Events\MonitoringEventEvent;
use SoftHouse\MonitoringService\Events\MonitoringGateEvent;
use SoftHouse\MonitoringService\FormatModel;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;

class GateWatcher extends Watcher
{
    use FetchesStackTrace;

    public function register($app)
    {
        $app['events']->listen(GateEvaluated::class, [$this, 'handleGateEvaluated']);
    }

    public function handleGateEvaluated(GateEvaluated $event)
    {
        $this->recordGateCheck($event->user, $event->ability, $event->result, $event->arguments);
    }

    public function recordGateCheck($user, $ability, $result, $arguments)
    {
        if (!MonitoringService::isRecording()) {
            return $result;
        }

        if ($this->shouldIgnore($ability)) {
            return $result;
        }

        $caller = $this->getCallerFromStackTrace([0, 1]);

        $entry = IncomingEntry::make([
            'ability' => $ability,
            'result' => $this->gateResult($result),
            'arguments' => $this->formatArguments($arguments),
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
        ]);

        MonitoringService::recordGate($entry);

        if (MonitoringService::isEnabledNotification(self::class)) {
            event(new MonitoringGateEvent($entry));
        }

        return $result;
    }

    private function shouldIgnore($ability)
    {
        return Str::is($this->options['ignore_abilities'] ?? [], $ability);
    }

    private function gateResult($result)
    {
        if ($result instanceof Response) {
            return $result->allowed() ? 'allowed' : 'denied';
        }

        return $result ? 'allowed' : 'denied';
    }

    private function formatArguments($arguments)
    {
        return collect($arguments)->map(function ($argument) {
            return $argument instanceof Model ? FormatModel::given($argument) : $argument;
        })->toArray();
    }
}
