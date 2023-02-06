<?php

namespace SoftHouse\MonitoringService;

class EntryType
{
    public const BATCH = 'batch';
    public const COMMAND = 'command';
    public const EVENT = 'event';
    public const EXCEPTION = 'exception';
    public const JOB = 'job';
    public const LOG = 'log';
    public const MODEL = 'model';
    public const REQUEST = 'request';
    public const SCHEDULED_TASK = 'schedule';
    public const GATE = 'gate';
}
