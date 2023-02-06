<?php

namespace SoftHouse\MonitoringService\Loggly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

trait LogglyModel
{
    protected static function bootLogglyModel(): void
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            if ($eventName === 'updated') {
                static::updating(function (Model $model) {

                    if (isset($model->getRawOriginal()['deleted_at'])) {
                        if ($model->getRawOriginal()['deleted_at'] === null) {
                            loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'oldValues' => $model->getRawOriginal(), 'newValues' => $model->getAttributes()])->log("Updated record.");
                        } else {
                            loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'oldValues' => $model->getRawOriginal(), 'newValues' => $model->getAttributes()])->log("Restored record.");
                        }
                    } else {
                        loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'oldValues' => $model->getRawOriginal(), 'newValues' => $model->getAttributes()])->log("Updated record.");
                    }
                });
            }

            static::$eventName(function (Model $model) use ($eventName) {

                if ($eventName === 'created') {
                    loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'values' => $model->getAttributes()])->log("Created record.");
                }

                if ($eventName === 'deleted') {
                    if(collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)){
                        if ($model->isForceDeleting() === false) {
                            loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'values' => $model->getAttributes()])->log("Deleted record.");
                        } else {
                            loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'values' => $model->getAttributes()])->log("Force deleted record.");
                        }
                    }else{
                        loggly()->performedOn($model)->withProperties(['model' => get_class($model), 'values' => $model->getAttributes()])->log("Deleted record.");
                    }
                }
            });
        });
    }

    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }
}
