<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use ReflectionFunction;
use SoftHouse\MonitoringService\Events\MonitoringEventEvent;
use SoftHouse\MonitoringService\ExtractProperties;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;
use SoftHouse\MonitoringService\Watchers\Formats\FormatsClosure;

class EventWatcher extends Watcher
{
    use FormatsClosure;


    public function register($app)
    {
        $app['events']->listen('*', [$this, 'recordEvent']);
    }


    public function recordEvent($eventName, $payload)
    {
        if(!MonitoringService::isRecording()) return;

        if($this->shouldIgnore($eventName)) return;

        $formattedPayload = $this->extractPayload($eventName, $payload);

        $entry = IncomingEntry::make([
            'name' => $eventName,
            'payload' => empty($formattedPayload) ? null : $formattedPayload,
            'listeners' => $this->formatListeners($eventName),
            'broadcast' => class_exists($eventName) && in_array(ShouldBroadcast::class, (array)class_implements($eventName)),
        ]);

        MonitoringService::recordEvent($entry);

        if(MonitoringService::isEnabledNotification(self::class)){
            event(new MonitoringEventEvent($entry));
        }
    }


    protected function extractPayload($eventName, $payload): array
    {
        if (class_exists($eventName) && isset($payload[0]) && is_object($payload[0])) {
            return ExtractProperties::from($payload[0]);
        }

        return collect($payload)->map(function ($value) {
            return is_object($value) ? [
                'class' => get_class($value),
                'properties' => json_decode(json_encode($value), true),
            ] : $value;
        })->toArray();
    }

    protected function formatListeners($eventName): array
    {
        return collect(app('events')->getListeners($eventName))
            ->map(function ($listener) {
                $listener = (new ReflectionFunction($listener))
                    ->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return Str::contains($listener, '@') ? $listener : $listener.'@handle';
                } elseif (is_array($listener) && is_string($listener[0])) {
                    return $listener[0].'@'.$listener[1];
                } elseif (is_array($listener) && is_object($listener[0])) {
                    return get_class($listener[0]).'@'.$listener[1];
                }

                return $this->formatClosureListener($listener);
            })->reject(function ($listener) {
                return Str::contains($listener, 'SoftHouse\\MonitoringService');
            })->map(function ($listener) {
                if (Str::contains($listener, '@')) {
                    $queued = in_array(ShouldQueue::class, class_implements(explode('@', $listener)[0]));
                }

                return [
                    'name' => $listener,
                    'queued' => $queued ?? false,
                ];
            })->values()->toArray();
    }

    protected function shouldIgnore($eventName): bool
    {
        return $this->eventIsIgnored($eventName) ||
            (MonitoringService::$ignoreFrameworkEvents && $this->eventIsFiredByTheFramework($eventName));
    }

    protected function eventIsFiredByTheFramework($eventName): bool
    {
        return Str::is(
            ['Illuminate\*', 'Laravel\Octane\*', 'eloquent*', 'bootstrapped*', 'bootstrapping*', 'creating*', 'composing*'],
            $eventName
        );
    }

    protected function eventIsIgnored($eventName): bool
    {
        return Str::is($this->options['ignore'] ?? [], $eventName);
    }
}
