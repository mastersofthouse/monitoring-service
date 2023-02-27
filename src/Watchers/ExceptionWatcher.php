<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use SoftHouse\MonitoringService\Events\MonitoringExceptionEvent;
use SoftHouse\MonitoringService\ExceptionContext;
use SoftHouse\MonitoringService\ExtractTags;
use SoftHouse\MonitoringService\IncomingExceptionEntry;
use SoftHouse\MonitoringService\MonitoringService;
use Throwable;

class ExceptionWatcher  extends Watcher
{
    public function register($app)
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordException']);
    }

    public function recordException(MessageLogged $event)
    {
        if (!MonitoringService::isRecording()) {
            return;
        }

        if ($this->shouldIgnore($event)) {
            return;
        }

        $exception = $event->context['exception'];

        $trace = collect($exception->getTrace())->map(function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        $entry = IncomingExceptionEntry::make($exception, [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
            'context' => transform(Arr::except($event->context, ['exception', 'monitoring']), function ($context) {
                return !empty($context) ? $context : null;
            }),
            'trace' => $trace,
            'line_preview' => ExceptionContext::get($exception),
        ]);
        MonitoringService::recordException($entry);

        if(MonitoringService::isEnabledNotification(self::class)){
            event(new MonitoringExceptionEvent($entry));
        }
    }

    protected function tags($event): array
    {
        return array_merge(ExtractTags::from($event->context['exception']),
            $event->context['monitoring-service'] ?? []
        );
    }

    private function shouldIgnore($event): bool
    {
        return !isset($event->context['exception']) ||
            !$event->context['exception'] instanceof Throwable;
    }
}
