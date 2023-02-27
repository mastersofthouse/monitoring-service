<?php

namespace SoftHouse\MonitoringService;
use Illuminate\Contracts\Debug\ExceptionHandler;

class IncomingExceptionEntry extends IncomingEntry
{
    public $exception;

    public function __construct($exception, array $content)
    {
        $this->exception = $exception;

        parent::__construct($content);
    }

    public function isReportableException()
    {
        $handler = app(ExceptionHandler::class);

        return method_exists($handler, 'shouldReport')
            ? $handler->shouldReport($this->exception) : true;
    }

    public function isException()
    {
        return true;
    }

    public function familyHash()
    {
        return md5($this->content['file'].$this->content['line']);
    }
}
