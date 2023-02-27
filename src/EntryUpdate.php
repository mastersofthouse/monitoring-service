<?php

namespace SoftHouse\MonitoringService;

class EntryUpdate
{
    public $uuid;

    public $type;

    public $changes = [];

    public $tagsChanges = ['removed' => [], 'added' => []];

    public function __construct($uuid, $type, array $changes)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->changes = $changes;
    }

    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    public function change(array $changes)
    {
        $this->changes = array_merge($this->changes, $changes);

        return $this;
    }

    public function addTags(array $tags)
    {
        $this->tagsChanges['added'] = array_unique(
            array_merge($this->tagsChanges['added'], $tags)
        );

        return $this;
    }

    public function removeTags(array $tags)
    {
        $this->tagsChanges['removed'] = array_unique(
            array_merge($this->tagsChanges['removed'], $tags)
        );

        return $this;
    }
}
