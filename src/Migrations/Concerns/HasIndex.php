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
        $key = array_wrap($key);

        if ( count($key) >= 2 ){
            $key = array_map(function($key){
                return $this->removeEverySecondCharInMiddle($key);
            }, $key);
        }

        $key = implode('_', $key);

        return $model->getTable().'_'.$key.'_'.($prefix ?: 'foreign');
    }

    /**
     * Returns if table has index builded from column name and table name.
     * @param  AdminModel $model
     * @param  string|array     $key
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
        $indexes = $model->getConnection()->getSchemaBuilder()->getIndexes($model->getTable());

        return collect($indexes)->keyBy('name')->toArray();
    }


    /**
     * Return indexes of model
     *
     * @param  AdminModel  $model
     * @return  string
     */
    protected function getModelForeignKeys(AdminModel $model)
    {
        $keys = $model->getConnection()->getSchemaBuilder()->getForeignKeys($model->getTable());

        return collect($keys)->keyBy('name')->toArray();
    }

    /*
     * Drops foreign key in table
     */
    protected function dropIndex($model, $key, $prefix = null)
    {
        $connection = $model->getConnection();

        $expression = dbRaw(
            'alter table `'.$model->getTable().'` drop '.($prefix ?: 'foreign key').' `'.$this->getIndexName($model, $key, $prefix).'`',
            $connection
        );

        return $connection->select($expression);
    }

    /*
     * Drops foreign key in table
     */
    protected function addIndex($model, $key, $prefix = null)
    {
        $connection = $model->getConnection();

        $expression = dbRaw(
            'alter table `'.$model->getTable().'` add INDEX '.$this->getIndexName($model, $key, $prefix).' (`'.$key.'`)',
            $connection
        );

        return $connection->select($expression);
    }
}
