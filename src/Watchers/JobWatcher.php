<?php

namespace SoftHouse\MonitoringService\Watchers;

use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\EntryUpdate;
use SoftHouse\MonitoringService\Events\MonitoringJobEvent;
use SoftHouse\MonitoringService\Events\MonitoringJobExceptionEvent;
use SoftHouse\MonitoringService\ExceptionContext;
use SoftHouse\MonitoringService\ExtractProperties;
use SoftHouse\MonitoringService\ExtractTags;
use SoftHouse\MonitoringService\IncomingEntry;
use SoftHouse\MonitoringService\MonitoringService;

class JobWatcher extends Watcher
{
    public function register($app)
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['monitoring_uuid' => optional($this->recordJob($connection, $queue, $payload))->uuid];
        });

        $app['events']->listen(JobProcessed::class, [$this, 'recordProcessedJob']);
        $app['events']->listen(JobFailed::class, [$this, 'recordFailedJob']);
    }

    public function recordJob($connection, $queue, array $payload)
    {
        if (!MonitoringService::isRecording()) {
            return;
        }

        $content = array_merge([
            'status' => 'pending',
        ], $this->defaultJobData($connection, $queue, $payload, $this->data($payload)));

        $entry = IncomingEntry::make($content)
            ->withFamilyHash($content['data']['batchId'] ?? null);

        MonitoringService::recordJob($entry);

        if (MonitoringService::isEnabledNotification(self::class)
            && MonitoringService::isEnabledNotificationJOB('pending')) {
            event(new MonitoringJobEvent($entry));
        }

        return $entry;
    }

    public function recordProcessedJob(JobProcessed $event)
    {
        if (!MonitoringService::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['monitoring_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $entry = EntryUpdate::make(
            $uuid, EntryType::JOB, ['status' => 'processed']
        );
        MonitoringService::recordUpdate($entry);

        $this->updateBatch($event->job->payload());

        $content = array_merge([
            'status' => 'processed',
            'name' => $event->job->payload()['displayName']
        ], []);

        if (MonitoringService::isEnabledNotification(self::class)
            && MonitoringService::isEnabledNotificationJOB('processed')) {
            event(new MonitoringJobEvent(IncomingEntry::make($content)));
        }
    }

    public function recordFailedJob(JobFailed $event)
    {
        if (!MonitoringService::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['monitoring_uuid'] ?? null;

        if (!$uuid) {
            return;
        }

        $entry = EntryUpdate::make(
            $uuid, EntryType::JOB, [
            'status' => 'failed',
            'displayName' => $event->job->payload()['displayName'],
            'exception' => [
                'message' => $event->exception->getMessage(),
                'trace' => $event->exception->getTrace(),
                'line' => $event->exception->getLine(),
                'line_preview' => ExceptionContext::get($event->exception),
            ],
        ]);

        MonitoringService::recordUpdate($entry);

        if (MonitoringService::isEnabledNotification(self::class)
            && MonitoringService::isEnabledNotificationJOB('failed')) {
            event(new MonitoringJobExceptionEvent([
                'uuid' => $uuid,
                'status' => 'failed',
                'type' => EntryType::JOB . ' failed',
                'displayName' => $event->job->payload()['displayName'],
                'exception' => [
                    'message' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTrace(),
                    'line' => $event->exception->getLine(),
                    'line_preview' => ExceptionContext::get($event->exception),
                ],
            ]));
        }
    }

    protected function defaultJobData($connection, $queue, array $payload, array $data)
    {
        return [
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload['displayName'],
            'tries' => $payload['maxTries'],
            'timeout' => $payload['timeout'],
            'data' => $data,
        ];
    }

    protected function data(array $payload)
    {
        if (!isset($payload['data']['command'])) {
            return $payload['data'];
        }

        return ExtractProperties::from(
            $payload['data']['command']
        );
    }

    protected function tags(array $payload)
    {
        if (!isset($payload['data']['command'])) {
            return [];
        }

        return ExtractTags::fromJob(
            $payload['data']['command']
        );
    }

    protected function updateBatch($payload)
    {
        $command = $this->getCommand($payload['data']);

        $properties = ExtractProperties::from(
            $command
        );

        if (isset($properties['batchId'])) {
            $batch = app(BatchRepository::class)->find($properties['batchId']);

            if (is_null($batch)) {
                return;
            }

            MonitoringService::recordUpdate(EntryUpdate::make(
                $properties['batchId'], EntryType::BATCH, $batch->toArray()
            ));
        }
    }

    protected function getCommand(array $data)
    {
        if (Str::startsWith($data['command'], 'O:')) {
            return unserialize($data['command']);
        }

        if (app()->bound(Encrypter::class)) {
            return unserialize(app(Encrypter::class)->decrypt($data['command']));
        }

        throw new RuntimeException('Unable to extract job payload.');
    }
}
