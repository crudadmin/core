<?php

namespace Admin\Core\Contracts\Migrations\Fields;

use Admin\Core\Contracts\DataStore;
use Admin\Core\Contracts\Migrations\Concerns\HasIndex;
use Admin\Core\Contracts\Migrations\Concerns\MigrationEvents;
use Admin\Core\Contracts\Migrations\MigrationBuilder;

class Field
{
    use HasIndex,
        MigrationEvents,
        DataStore;

    /*
     * We want share events between MigrationBuilder and columns classes
     */
    protected function getStoreKey()
    {
        return MigrationBuilder::class;
    }
}