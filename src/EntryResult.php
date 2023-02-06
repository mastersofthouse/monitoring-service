<?php

namespace SoftHouse\MonitoringService;

use Carbon\Carbon;
use JsonSerializable;

class EntryResult implements JsonSerializable
{

    public mixed $id;
    public mixed $uuid;
    public mixed $batchId;
    public mixed $type;
    public mixed $familyHash;
    public mixed $content = [];
    public mixed $createdAt;
    public mixed $batch = [];

    public function __construct($id, $uuid, string $batchId, string $type, ?string $familyHash, array $content, $createdAt, $batch = [])
    {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->type = $type;
        $this->batchId = $batchId;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->familyHash = $familyHash;
        $this->batch = $batch;
    }

    public function jsonSerialize()
    {
        $collect = collect([
            'id' => $this->id,
            'uuid' => $this->uuid,
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => $this->content,
            'family_hash' => $this->familyHash,
            'created_at' => Carbon::parse($this->createdAt)->format('Y-m-d H:i:s'),
        ]);

        if (count($this->batch) > 0) {
            $collect->put('batchs', $this->batch);
        }

        return $collect->all();
    }
}
