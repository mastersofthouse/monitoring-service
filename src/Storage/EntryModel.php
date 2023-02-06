<?php

namespace SoftHouse\MonitoringService\Storage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryModel extends Model
{
    use HasFactory;

    protected $table = 'monitoring';

    const UPDATED_AT = null;

    protected $casts = [
        'content' => 'json',
    ];

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;
}
