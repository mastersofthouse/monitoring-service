<?php

namespace SoftHouse\MonitoringService\Storage;

use Illuminate\Http\Request;

class EntryQueryOptions
{
    public $batchId;

    public $tag;

    public $familyHash;

    public $beforeSequence;

    public $uuids;

    public $limit = 50;

    public static function fromRequest(Request $request)
    {
        return (new static)
            ->batchId($request->batch_id)
            ->uuids($request->uuids)
            ->beforeSequence($request->before)
            ->tag($request->tag)
            ->familyHash($request->family_hash)
            ->limit($request->take ?? 50);
    }

    public static function forBatchId(?string $batchId)
    {
        return (new static)->batchId($batchId);
    }

    public function batchId(?string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function uuids(?array $uuids)
    {
        $this->uuids = $uuids;

        return $this;
    }

    public function beforeSequence($id)
    {
        $this->beforeSequence = $id;

        return $this;
    }

    public function tag(?string $tag)
    {
        $this->tag = $tag;

        return $this;
    }

    public function familyHash(?string $familyHash)
    {
        $this->familyHash = $familyHash;

        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }
}
