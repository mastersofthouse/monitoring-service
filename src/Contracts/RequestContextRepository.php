<?php

namespace SoftHouse\MonitoringService\Contracts;

interface RequestContextRepository
{
    public static function getIP(): string;

    public static function getInfoIP($ip = null): array;

    public static function getDevice(): array;

}
