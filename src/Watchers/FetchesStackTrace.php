<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Support\Str;

trait FetchesStackTrace
{
    protected function getCallerFromStackTrace($forgetLines = 0)
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget($forgetLines);

        return $trace->first(function ($frame) {
            if (! isset($frame['file'])) {
                return false;
            }

            return ! Str::contains($frame['file'],
                base_path('vendor'.DIRECTORY_SEPARATOR.$this->ignoredVendorPath())
            );
        });
    }

    protected function ignoredVendorPath()
    {
        if (! ($this->options['ignore_packages'] ?? true)) {
            return 'laravel';
        }
    }
}
