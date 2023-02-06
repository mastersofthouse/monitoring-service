<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\EventFake;
use SoftHouse\MonitoringService\Contracts\EntriesRepository;
use SoftHouse\MonitoringService\Contracts\TerminableRepository;
use SoftHouse\MonitoringService\Events\MonitoringCommandEvent;
use SoftHouse\MonitoringService\Events\MonitoringEventEvent;
use SoftHouse\MonitoringService\Events\MonitoringExceptionEvent;
use SoftHouse\MonitoringService\Events\MonitoringGateEvent;
use SoftHouse\MonitoringService\Events\MonitoringJobEvent;
use SoftHouse\MonitoringService\Events\MonitoringJobExceptionEvent;
use SoftHouse\MonitoringService\Events\MonitoringRequestEvent;
use SoftHouse\MonitoringService\Events\MonitoringScheduleEvent;
use SoftHouse\MonitoringService\Watchers\EventWatcher;
use Throwable;

class MonitoringService
{
    use ListensForStorageOpportunities;
    protected static array $Watchers = [];
    public static bool $shouldRecord = false;
    public static array $filterUsing = [];
    public static array $entriesQueue = [];
    public static $afterRecordingHook;
    public static array $updatesQueue = [];
    public static array $filterBatchUsing = [];
    public static array $afterStoringHooks = [];
    protected static array $watchersNotifications = [];
    protected static array $watchersNotificationsJOB = [];
    protected static array $logglyNotifications = [];
    public static bool $ignoreFrameworkEvents = true;
    public static array $hiddenRequestHeaders = ['authorization', 'php-auth-pw'];
    public static array $hiddenRequestParameters = ['password', 'password_confirmation'];
    public static array $hiddenResponseParameters = [];

    public static function start($app): void
    {
        if (!config('monitoring-service.enabled')) {
            return;
        }

        try {
            if(!\Illuminate\Support\Facades\Schema::connection(
                config("monitoring-service.storage." . config('monitoring-service.driver') . ".connection")
            )->hasTable('monitoring')){
                return;
            }
        }catch (\Exception $exception){
            return;
        }

        static::registerWatchers($app);

        static::startRecording();
    }

    public static function registerWatchers($app)
    {
        foreach (config('monitoring-service.watchers') as $key => $watcher) {
            if (is_string($key) && $watcher === false) {
                continue;
            }

            if (is_array($watcher) && !($watcher['enabled'] ?? true)) {
                continue;
            }

            if($key === EventWatcher::class){
                if(!is_array($watcher['ignore'])){
                    $watcher['ignore'] = [];
                }

                $watcher['ignore'][] = MonitoringCommandEvent::class;
                $watcher['ignore'][] = MonitoringEventEvent::class;
                $watcher['ignore'][] = MonitoringExceptionEvent::class;
                $watcher['ignore'][] = MonitoringGateEvent::class;
                $watcher['ignore'][] = MonitoringJobEvent::class;
                $watcher['ignore'][] = MonitoringJobExceptionEvent::class;
                $watcher['ignore'][] = MonitoringRequestEvent::class;
                $watcher['ignore'][] = MonitoringScheduleEvent::class;
//                $watcher['ignore'][] = MessageLogged::class;
            }

            $watcher = $app->make(is_string($key) ? $key : $watcher, [
                'options' => is_array($watcher) ? $watcher : [],
            ]);

            static::$Watchers[] = get_class($watcher);

            $watcher->register($app);
        }

        foreach (config('monitoring-service.events_watchers') as $key => $watcher) {

            if(gettype($watcher) === "boolean"){
                static::$watchersNotifications[$key] = $watcher;
            }
        }

        foreach (config('monitoring-service.events_job') as $key => $watcher) {

            if(gettype($watcher) === "boolean"){
                static::$watchersNotificationsJOB[$key] = $watcher;
            }
        }

        foreach (config('monitoring-service.events_loggy') as $key => $watcher) {

            if(gettype($watcher) === "boolean"){
                static::$logglyNotifications[$key] = $watcher;
            }
        }
    }

    public static function isEnabledNotification($watcher)
    {
        return array_key_exists($watcher, static::$watchersNotifications) ? static::$watchersNotifications[$watcher] : null;
    }

    public static function startRecording(): void
    {
        static::$shouldRecord = !cache('monitoring-service:pause-recording');
    }

    public static function isRecording(): bool
    {
        return static::$shouldRecord && !app('events') instanceof EventFake;
    }

    public static function stopRecording(): void
    {
        static::$shouldRecord = false;
    }

    public static function recordCommand(IncomingEntry $entry): void
    {
        static::record(EntryType::COMMAND, $entry);
    }

    public static function recordEvent(IncomingEntry $entry): void
    {
        static::record(EntryType::EVENT, $entry);
    }

    public static function recordException(IncomingEntry $entry): void
    {
        static::record(EntryType::EXCEPTION, $entry);
    }

    public static function recordGate(IncomingEntry $entry)
    {
        static::record(EntryType::GATE, $entry);
    }

    public static function recordJob($entry)
    {
        static::record(EntryType::JOB, $entry);
    }

    public static function recordRequest(IncomingEntry $entry)
    {
        static::record(EntryType::REQUEST, $entry);
    }

    public static function recordScheduledCommand(IncomingEntry $entry)
    {
        static::record(EntryType::SCHEDULED_TASK, $entry);
    }

    public static function recordLoggly(IncomingEntry $entry)
    {
        static::record(EntryType::LOG, $entry);
    }

    protected static function record(string $type, IncomingEntry $entry): void
    {
        if (!static::isRecording()) {
            return;
        }

        $entry->type = $type;

        try {
            if (Auth::hasResolvedGuards() && Auth::hasUser()) {
                $entry->user(Auth::user());
            }
        } catch (Throwable $e) {
        }

        static::withoutRecording(function () use ($entry) {
            if (collect(static::$filterUsing)->every->__invoke($entry)) {
                static::$entriesQueue[] = $entry;
            }

            if (static::$afterRecordingHook) {
                call_user_func(static::$afterRecordingHook, new static, $entry);
            }
        });
    }

    public static function withoutRecording($callback): void
    {
        $shouldRecord = static::$shouldRecord;

        static::$shouldRecord = false;

        try {
            call_user_func($callback);
        } finally {
            static::$shouldRecord = $shouldRecord;
        }
    }

    public static function store(EntriesRepository $storage)
    {
        if (empty(static::$entriesQueue) && empty(static::$updatesQueue)) {
            return;
        }

        static::withoutRecording(function () use ($storage) {
            if (!collect(static::$filterBatchUsing)->every->__invoke(collect(static::$entriesQueue))) {
                static::flushEntries();
            }

            try {
                $batchId = Str::orderedUuid()->toString();

                $storage->store(static::collectEntries($batchId));
                $storage->update(static::collectUpdates($batchId));

                if ($storage instanceof TerminableRepository) {
                    $storage->terminate();
                }

                collect(static::$afterStoringHooks)->every->__invoke(static::$entriesQueue, $batchId);
            } catch (Throwable $e) {
                app(ExceptionHandler::class)->report($e);
            }
        });

        static::$entriesQueue = [];
        static::$updatesQueue = [];
    }

    public static function flushEntries(): static
    {
        static::$entriesQueue = [];

        return new static;
    }

    protected static function collectEntries($batchId): \Illuminate\Support\Collection
    {
        return collect(static::$entriesQueue)
            ->each(function ($entry) use ($batchId) {
                $entry->batchId($batchId);

                if ($entry->isDump()) {
                    $entry->assignEntryPointFromBatch(static::$entriesQueue);
                }
            });
    }

    public static function recordUpdate(EntryUpdate $update)
    {
        if (static::$shouldRecord) {
            static::$updatesQueue[] = $update;
        }
    }

    protected static function collectUpdates($batchId): \Illuminate\Support\Collection
    {
        return collect(static::$updatesQueue)
            ->each(function ($entry) use ($batchId) {
                $entry->change(['updated_batch_id' => $batchId]);
            });
    }

    public static function isEnabledNotificationJOB($watcher)
    {
        return array_key_exists($watcher, static::$watchersNotificationsJOB) ? static::$watchersNotificationsJOB[$watcher] : null;
    }

    public static function isEnabledNotificationLogLevel($watcher)
    {

        return array_key_exists($watcher, static::$logglyNotifications) ? static::$logglyNotifications[$watcher] : null;
    }
}
