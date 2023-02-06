<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FormatModel
{
    public static function given($model): string
    {
        return get_class($model).':'.implode('_', Arr::wrap($model->getKey()));
    }
}
