<?php

namespace SoftHouse\MonitoringService;

use SoftHouse\MonitoringService\Contracts\EntriesRepository;
use SoftHouse\MonitoringService\Contracts\MonitoringRepository;

class MonitoringEntriesRepository implements MonitoringRepository
{

    private EntriesRepository $entriesRepository;

    public function __construct(EntriesRepository $entriesRepository)
    {
        $this->entriesRepository = $entriesRepository;
    }

    public function all()
    {
        return $this->entriesRepository->all();
    }

    public function batch($id)
    {
        return $this->entriesRepository->batch($id);
    }

    public function find($id)
    {
        return $this->entriesRepository->find($id);
    }

    public function getType($type)
    {
        return $this->entriesRepository->getType($type);
    }
}
