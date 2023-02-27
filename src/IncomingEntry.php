<?php

namespace SoftHouse\MonitoringService;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use SoftHouse\MonitoringService\Contracts\EntriesRepository;

class IncomingEntry
{
    public mixed $uuid;

    public $batchId;

    public $type;

    public $user;

    public $content = [];

    public $familyHash;

    public mixed $recordedAt;

    public function __construct(array $content, $uuid = null)
    {
        $this->uuid = $uuid ?: (string)Str::orderedUuid();

        $this->recordedAt = now();

        $this->content = array_merge($content, ['hostname' => gethostname()]);

        $stdClass = config('monitoring-service.extra-info', null);

        if (is_null($stdClass)) {
            $this->content = array_merge($this->content, ['extra' => null]);
        } else {
            $this->content = array_merge($this->content, ['extra' => $stdClass::get()]);
        }
    }

    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    public function batchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function withFamilyHash($familyHash)
    {
        $this->familyHash = $familyHash;

        return $this;
    }

    public function user($user)
    {
        $this->user = $user;

        $this->content = array_merge($this->content, [
            'user' => [
                'id' => $user->getAuthIdentifier(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ],
        ]);

        return $this;
    }

    public function isRequest(): bool
    {
        return $this->type === EntryType::REQUEST;
    }

    public function isFailedRequest(): bool
    {
        return $this->type === EntryType::REQUEST &&
            ($this->content['response_status'] ?? 200) >= 500;
    }

    public function isGate(): bool
    {
        return $this->type === EntryType::GATE;
    }

    public function isFailedJob(): bool
    {
        return $this->type === EntryType::JOB &&
            ($this->content['status'] ?? null) === 'failed';
    }

    public function isReportableException()
    {
        return false;
    }

    public function isException()
    {
        return false;
    }

    public function isDump()
    {
        return false;
    }

    public function isScheduledTask(): bool
    {
        return $this->type === EntryType::SCHEDULED_TASK;
    }

    public function familyHash()
    {
        return $this->familyHash;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'batch_id' => $this->batchId,
            'family_hash' => $this->familyHash,
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->recordedAt->toDateTimeString(),
        ];
    }
}
