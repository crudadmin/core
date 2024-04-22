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

            return collect($new)->keyBy('name')->toArray();
        });

        return $columns[$key] ?? null;
    }

    public function getColumnTypeName(AdminModel $model, $key)
    {
        $column = $this->getTableColumn($model, $key);

        return $column ? $column['type_name'] : null;
    }
}

?>