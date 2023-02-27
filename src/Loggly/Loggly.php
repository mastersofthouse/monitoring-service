<?php

namespace SoftHouse\MonitoringService\Loggly;

use Illuminate\Database\Eloquent\Model;
use SoftHouse\MonitoringService\Events\MonitoringLogglyEvent;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;

class Loggly
{
    private array $logLevel = ["emergency", "alert", "critical", "error", "warning", "notice", "info", "model", "debug"];
    const arrayLevel = [self::emergency, self::alert, self::critical, self::error, self::warning, self::notice, self::info, self::model, self::debug];

    const emergency = 0;
    const alert = 1;
    const critical = 2;
    const error = 3;
    const warning = 4;
    const notice = 5;
    const info = 6;
    const model = 7;
    const debug = 8;

    private string $level = "info";

    private array $properties = [];

    private ?string $performed = null;

    private ?string $performedID = null;

    private $exception = null;

    public function __construct()
    {
        return $this;
    }

    public function level($level): Loggly
    {
        $this->level = $this->logLevel[$level];
        return $this;
    }

    public function withProperties($properties = []): Loggly
    {
        $this->properties[] = $properties;
        return $this;
    }

    public function performedOn($performed): Loggly
    {
        if (gettype($performed) === "object" && $performed instanceof Model === true) {
            $this->performed = get_class($performed);
            $this->performedID = $performed->getKey();
        } else if (gettype($performed) === "object") {
            $this->performed = get_class($performed);
        } else {
            $this->performed = $performed;
        }
        return $this;
    }

    public function exception($exception): Loggly
    {
        if (gettype($exception) === "object" && $exception instanceof \Throwable) {
            $this->exception = [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
            ];
        } else if (gettype($exception) === "object") {
            $this->exception = $exception;
        } else if (gettype($exception) === "string") {
            $this->exception = $exception;
        }

        return $this;
    }

    public function log($message)
    {
        $entry = IncomingEntry::make(
            [
                'level' => $this->level,
                'message' => $message,
                'properties' => $this->properties,
                'performed' => $this->performed,
                'performed_id' => $this->performedID,
                'exception' => $this->exception,
            ]
        );

        MonitoringService::recordLoggly($entry);

        if (MonitoringService::isEnabledNotification(self::class)
            && MonitoringService::isEnabledNotificationLogLevel(self::arrayLevel[array_search($this->level, $this->logLevel)])) {
            event(new MonitoringLogglyEvent($entry));
        }
    }
}
