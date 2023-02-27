<?php

namespace SoftHouse\MonitoringService\Watchers\Formats;

use Closure;
use ReflectionException;
use ReflectionFunction;

trait FormatsClosure
{
    /**
     * @throws ReflectionException
     */
    protected function formatClosureListener(Closure $listener): string
    {
        $listener = new ReflectionFunction($listener);

        return sprintf('Closure at %s[%s:%s]',
            $listener->getFileName(),
            $listener->getStartLine(),
            $listener->getEndLine()
        );
    }
}
