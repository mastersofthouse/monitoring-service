<?php

namespace SoftHouse\MonitoringService\Storage;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use SoftHouse\MonitoringService\Contracts\EntriesRepository as Contract;
use SoftHouse\MonitoringService\EntryResult;
use SoftHouse\MonitoringService\EntryType;
use SoftHouse\MonitoringService\IncomingEntry;

class DatabaseEntriesRepository implements Contract
{

    protected string $connection;

    protected int $chunkSize = 1000;

    protected mixed $monitoredTags;


    public function __construct(string $connection, int $chunkSize = null)
    {
        $this->connection = $connection;

        if ($chunkSize) {
            $this->chunkSize = $chunkSize;
        }
    }

    public function all()
    {
        $data = EntryModel::on($this->connection)
            ->orderBy('created_at', 'DESC')
            ->orderBy('sequence', 'DESC')
            ->get()
            ->toArray();

        $d = [];
        foreach ($data as $key => $value) {

            $d[] = (new EntryResult($key, $value['uuid'], $value['sequence'],
                $value['batch_id'], $value['type'],
                $value['family_hash'], $value['content'], $value['created_at']))->jsonSerialize();
        }

        return $d;
    }

    public function get($type, EntryQueryOptions $options)
    {
        return EntryModel::on($this->connection)
            ->take($options->limit)
            ->orderByDesc('sequence')
            ->get()->reject(function ($entry) {
                return ! is_array($entry->content);
            })->map(function ($entry) {
                return new EntryResult(
                    $entry->sequence,
                    $entry->uuid,
                    $entry->batch_id,
                    $entry->type,
                    $entry->family_hash,
                    $entry->content,
                    $entry->created_at,
                    []
                );
            })->values();
    }

    public function batch($batchId): array
    {

        $data = EntryModel::on($this->connection)->where('batch_id', $batchId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('sequence', 'DESC')
            ->get()
            ->toArray();

        $d = [];
        foreach ($data as $key => $value) {

            if($value['batch_id'] === $batchId) continue;

            $d[] = (new EntryResult($value['sequence'], $value['uuid'],
                $value['batch_id'], $value['type'],
                $value['family_hash'], $value['content'], $value['created_at']))->jsonSerialize();
        }

        return $d;
    }

    public function find($id): array
    {

        $data = EntryModel::on($this->connection)->where('uuid', $id)
            ->orderBy('created_at', 'DESC')
            ->orderBy('sequence', 'DESC')
            ->get()
            ->toArray();

        $d = [];

        if (count($data) > 0) {
            $data = $data[0];
        } else {
            return $d;
        }

        $d[] = (new EntryResult($data['sequence'], $data['uuid'],
            $data['batch_id'], $data['type'],
            $data['family_hash'], $data['content'], $data['created_at']))->jsonSerialize();

        return $d;
    }

    public function getType($type): array
    {
        $data = EntryModel::on($this->connection)->where('type', $type)
            ->orderBy('created_at', 'DESC')
            ->orderBy('sequence', 'DESC')
            ->get()
            ->toArray();

        $d = [];
        foreach ($data as $key => $value) {

            $d[] = (new EntryResult($value['sequence'], $value['uuid'],
                $value['batch_id'], $value['type'],
                $value['family_hash'], $value['content'], $value['created_at']))->jsonSerialize();
        }

        return $d;
    }

    protected function table($table): \Illuminate\Database\Query\Builder
    {
        return DB::connection($this->connection)->table($table);
    }

    public function store(Collection $entries)
    {
        if ($entries->isEmpty()) {
            return;
        }

        [$exceptions, $entries] = $entries->partition->isException();

        $this->storeExceptions($exceptions);

        $table = $this->table('monitoring');

        $entries->chunk($this->chunkSize)->each(function ($chunked) use ($table) {
            $table->insert($chunked->map(function ($entry) {
                $entry->content = json_encode($entry->content);

                return $entry->toArray();
            })->toArray());
        });
    }

    protected function storeExceptions(Collection $exceptions)
    {
        $exceptions->chunk($this->chunkSize)->each(function ($chunked) {
            $this->table('monitoring')->insert($chunked->map(function ($exception) {
                $occurrences = $this->countExceptionOccurrences($exception);

                return array_merge($exception->toArray(), [
                    'family_hash' => $exception->familyHash(),
                    'content' => json_encode(array_merge(
                        $exception->content, ['occurrences' => $occurrences + 1]
                    )),
                ]);
            })->toArray());
        });

    }

    protected function countExceptionOccurrences(IncomingEntry $exception): int
    {
        return $this->table('monitoring')
            ->where('type', EntryType::EXCEPTION)
            ->where('family_hash', $exception->familyHash())
            ->count();
    }

    public function loadMonitoredTags()
    {
        try {
            $this->monitoredTags = $this->monitoring();
        } catch (\Throwable $e) {
            $this->monitoredTags = [];
        }
    }

    public function update(Collection $updates)
    {
        foreach ($updates as $update) {
            $entry = $this->table('monitoring')
                ->where('uuid', $update->uuid)
                ->where('type', $update->type)
                ->first();

            if (!$entry) {
                continue;
            }

            $content = json_encode(array_merge(
                json_decode($entry->content ?? $entry['content'] ?? [], true) ?: [], $update->changes
            ));

            $this->table('monitoring')
                ->where('uuid', $update->uuid)
                ->where('type', $update->type)
                ->update(['content' => $content]);
        }
    }

    public function monitoring(): array
    {
        return [];
    }
}
