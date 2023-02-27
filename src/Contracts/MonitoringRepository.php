<?php

namespace SoftHouse\MonitoringService\Contracts;


interface MonitoringRepository
{
    public function all();

    public function batch($id);

    public function find($id);

    public function getType($type);
}
