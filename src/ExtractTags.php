<?php

namespace SoftHouse\MonitoringService;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use ReflectionClass;
use stdClass;

class ExtractTags
{
    public static function from($target)
    {
        if ($tags = static::explicitTags([$target])) {
            return $tags;
        }

        return static::modelsFor([$target])->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    public static function fromJob($job)
    {
        if ($tags = static::extractExplicitTags($job)) {
            return $tags;
        }

        return static::modelsFor(static::targetsFor($job))->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    public static function fromArray(array $data)
    {
        return collect($data)->map(function ($value) {
            return static::resolveValue($value);
        })->collapse()->filter()->map(function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    protected static function extractExplicitTags($job)
    {
        return $job instanceof CallQueuedListener
            ? static::tagsForListener($job)
            : static::explicitTags(static::targetsFor($job));
    }

    protected static function tagsForListener($job)
    {
        return collect(
            [static::extractListener($job), static::extractEvent($job)]
        )->map(function ($job) {
            return static::from($job);
        })->collapse()->unique()->toArray();
    }

    protected static function explicitTags(array $targets)
    {
        return collect($targets)->map(function ($target) {
            return method_exists($target, 'tags') ? $target->tags() : [];
        })->collapse()->unique()->all();
    }

    protected static function targetsFor($job)
    {
        switch (true) {
            case $job instanceof BroadcastEvent:
                return [$job->event];
            case $job instanceof CallQueuedListener:
                return [static::extractEvent($job)];
            case $job instanceof SendQueuedMailable:
                return [$job->mailable];
            case $job instanceof SendQueuedNotifications:
                return [$job->notification];
            default:
                return [$job];
        }
    }

    protected static function modelsFor(array $targets)
    {
        return collect($targets)->map(function ($target) {
            return collect((new ReflectionClass($target))->getProperties())->map(function ($property) use ($target) {
                $property->setAccessible(true);

                if (PHP_VERSION_ID < 70400 || ! is_object($target) || $property->isInitialized($target)) {
                    return static::resolveValue($property->getValue($target));
                }
            })->collapse()->filter();
        })->collapse()->unique();
    }


    protected static function extractListener($job)
    {
        return (new ReflectionClass($job->class))->newInstanceWithoutConstructor();
    }


    protected static function extractEvent($job)
    {
        return isset($job->data[0]) && is_object($job->data[0])
            ? $job->data[0]
            : new stdClass;
    }


    protected static function resolveValue($value)
    {
        switch (true) {
            case $value instanceof Model:
                return collect([$value]);
            case $value instanceof Collection:
                return $value->flatten();
        }
    }
}
