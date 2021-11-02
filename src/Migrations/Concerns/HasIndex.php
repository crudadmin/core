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
     * Returns if table has index builded from column name and table name.
     * @param  AdminModel $model
     * @param  string     $key
     * @param  string     $prefix
     * @param  string     $indexKey
     * @return int
     */
    protected function hasIndex(AdminModel $model, $key, $prefix = null)
    {
        $indexes = $this->getModelIndexes($model);

        $searchIndex = $this->getIndexName($model, $key, $prefix);

        return array_key_exists($searchIndex, $indexes);
    }

    /**
     * Return indexes of model
     *
     * @param  AdminModel  $model
     * @return  string
     */
    protected function getModelIndexes(AdminModel $model)
    {
        $schema = $model->getConnection()->getDoctrineSchemaManager();

        return $schema->listTableIndexes($model->getTable());
    }


    /**
     * Return indexes of model
     *
     * @param  AdminModel  $model
     * @return  string
     */
    protected function getModelForeignKeys(AdminModel $model)
    {
        $schema = $model->getConnection()->getDoctrineSchemaManager();

        return $schema->listTableForeignKeys($model->getTable());
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

    /*
     * Drops foreign key in table
     */
    protected function addIndex($model, $key, $prefix = null)
    {
        return $model->getConnection()->select(
            DB::raw('alter table `'.$model->getTable().'` add INDEX '.$this->getIndexName($model, $key, $prefix).' (`'.$key.'`)')
        );
    }
}
