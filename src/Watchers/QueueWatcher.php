<?php

namespace SoftHouse\MonitoringService\Watchers;

use Carbon\Carbon;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use SoftHouse\MonitoringService\Events\MonitoringQueueEvent;
use SoftHouse\MonitoringService\MonitoringService;

class QueueWatcher extends Watcher
{
    public function register($app)
    {


        Queue::stopping(function (WorkerStopping $event) {

            if (MonitoringService::isRecording()) {
                if (is_file(storage_path('framework/queue_stopping'))) {
                    return 0;
                } else {
                    file_put_contents(storage_path('framework/queue_stopping'), Carbon::now()->timestamp);

                    if (MonitoringService::isEnabledNotification(self::class)) {
                        event(new MonitoringQueueEvent(true, null));
                    }
                }
            }
            return true;
        });

        Queue::looping(function (Looping $event) {

            try {
                if (MonitoringService::isRecording()) {
                    if (is_file(storage_path('framework/queue_stopping'))) {
                        $file = file_get_contents(storage_path('framework/queue_stopping'));

                        $time = Carbon::createFromTimestamp($file)->toDateTimeString();

                        File::delete(storage_path('framework/queue_stopping'));

                        if (MonitoringService::isEnabledNotification(self::class)) {
                            event(new MonitoringQueueEvent(false, $time));
                        }
                    }
                }
            } catch (\Exception $exception) {

            }
            return true;
        });

    }
}
