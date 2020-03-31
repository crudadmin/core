<?php

namespace Admin\Core\Migrations\Concerns;

use Illuminate\Support\Facades\DB;
use Admin\Core\Eloquent\AdminModel;

trait HasIndex
{
    /**
     * Returns foreign key name.
     * @param  AdminModel $model
     * @param  string     $key
     * @param  string     $prefix
     * @return string
     */
    protected function getIndexName(AdminModel $model, $key, $prefix = null)
    {
        return $model->getTable().'_'.$key.'_'.($prefix ?: 'foreign');
    }

    /**
     * Returns if table has index.
     * @param  AdminModel $model
     * @param  string     $key
     * @param  string     $prefix
     * @return int
     */
    protected function hasIndex(AdminModel $model, $key, $prefix = null)
    {
        $schema = $model->getConnection()->getDoctrineSchemaManager();
        $indexes = $schema->listTableIndexes($model->getTable());

        $searchIndex = $this->getIndexName($model, $key, $prefix);

        return array_key_exists($searchIndex, $indexes);
    }

    /*
     * Drops foreign key in table
     */
    protected function dropIndex($model, $key, $prefix = null)
    {
        return $model->getConnection()->select(
            DB::raw('alter table `'.$model->getTable().'` drop '.($prefix ?: 'foreign key').' `'.$this->getIndexName($model, $key, $prefix).'`')
        );
    }
}
