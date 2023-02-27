<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use SoftHouse\MonitoringService\Contracts\EntriesRepository;

trait ListensForStorageOpportunities
{

    protected static array $processingJobs = [];

    public static function listenForStorageOpportunities($app): void
    {
        static::storeEntriesBeforeTermination($app);
        static::storeEntriesAfterWorkerLoop($app);
    }

    protected static function storeEntriesBeforeTermination($app): void
    {
        $app->terminating(function () use ($app) {
            static::store($app[EntriesRepository::class]);
        });
    }


    protected static function storeEntriesAfterWorkerLoop($app): void
    {
        $app['events']->listen(JobProcessing::class, function ($event) {
            if ($event->connectionName !== 'sync') {
                static::startRecording();

                static::$processingJobs[] = true;
            }
        });

        $app['events']->listen(JobProcessed::class, function ($event) use ($app) {
            static::storeIfDoneProcessingJob($event, $app);
        });

        $app['events']->listen(JobFailed::class, function ($event) use ($app) {
            static::storeIfDoneProcessingJob($event, $app);
        });

        $app['events']->listen(JobExceptionOccurred::class, function () {
            array_pop(static::$processingJobs);
        });
    }

    protected static function storeIfDoneProcessingJob($event, $app): void
    {
        array_pop(static::$processingJobs);

        if (empty(static::$processingJobs) && $event->connectionName !== 'sync') {
            static::store($app[EntriesRepository::class]);
            static::stopRecording();
        }
    }
}
