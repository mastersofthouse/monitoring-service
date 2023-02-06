<?php

namespace SoftHouse\MonitoringService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SoftHouse\MonitoringService\Contracts\EntriesRepository;
use SoftHouse\MonitoringService\Storage\EntryQueryOptions;

abstract class EntryController extends Controller
{
    abstract protected function entryType();

    abstract protected function watcher();

    public function index(Request $request, EntriesRepository $storage): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'entries' => $storage->get(
                $this->entryType(),
                EntryQueryOptions::fromRequest($request)
            ),
            'status' => $this->status(),
        ]);
    }

    public function show(EntriesRepository $storage, $id): \Illuminate\Http\JsonResponse
    {
        $entry = $storage->find($id);

        return response()->json([
            'entry' => $entry,
            'batch' => count($entry) > 0 ? $storage->batch($entry[0]['batch_id']) : [],
        ]);
    }

    protected function status(): string
    {
        if (! config('monitoring-service.enabled', false)) {
            return 'disabled';
        }

        if (cache('monitoring-service:pause-recording', false)) {
            return 'paused';
        }

        $watcher = config('monitoring-service.watchers.'.$this->watcher());

        if (! $watcher || (isset($watcher['enabled']) && ! $watcher['enabled'])) {
            return 'off';
        }

        return 'enabled';
    }
}
