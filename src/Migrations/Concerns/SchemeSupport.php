<?php

namespace Admin\Core\Migrations\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Admin;

trait SchemeSupport
{
    public function getTableColumn(AdminModel $model, $key)
    {
        $columns = Admin::cache('migrations.columns.scheme.'.$model->getTable(), function() use ($model) {
            $new = $model->getConnection()->getSchemaBuilder()->getColumns(
                $model->getTable()
            );

            return collect($new)->groupBy('name')->map(function($row){
                return $row[0];
            })->toArray();
        });

        return @$columns[$key];
    }

    public function getColumnTypeName(AdminModel $model, $key)
    {
        return $this->getTableColumn($model, $key)['type_name'];
    }
}

?>