<?php

namespace SoftHouse\MonitoringService\Contracts;

use Illuminate\Support\Collection;
use SoftHouse\MonitoringService\Storage\EntryQueryOptions;

interface EntriesRepository
{

    public function all();

    public function get($type, EntryQueryOptions $options);

    public function batch($id);

    public function find($id);

    public function getType($type);

    public function store(Collection $entries);

    public function loadMonitoredTags();
}
