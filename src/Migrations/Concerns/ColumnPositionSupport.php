<?php

namespace Admin\Core\Migrations\Concerns;

use Fields;
use AdminCore;
use Admin\Core\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

trait SupportColumn
{
    /**
     * Add item into array into specific key position
     * @param  array &$array
     * @param  integer $key
     * @param  mixed $addItem
     * @return void
     */
    function arrayAddAfterKey(array &$array, int $key, $addItem)
    {
        if (($keyPos = array_search($key, array_keys($array))) === false){
            $array[] = $addItem;
            return;
        }

        $second_array = array_splice($array, $keyPos + 1);
        $array = array_merge($array, [$addItem], $second_array);
    }

    /**
     * Get last column in table builder with correct order
     * @param  array $columns
     * @param  array $onlyStaticColumn
     * @return array
     */
    private function getLastColumnInTable(array $columns, $onlyStaticColumn)
    {
        $columnKeys = array_map(function($column){
            return $column->name;
        }, $columns);

        foreach ($columns as $key => $column) {
            if ( ! $column->after )
                continue;

            //Remove column we want to move
            unset($columns[$key]);

            //Get next position of column after we want add actual column
            $addToPosition = array_search($column->after, $columnKeys) + 1;

            //Add column into specific position in array
            $this->arrayAddAfterKey($columns, $addToPosition, $column);
        }

        $allColumns = array_merge($columns, $onlyStaticColumn);

        return end($allColumns);
    }

    /**
     * Get only fields types columns from Blueprint table
     * And reverse order of new added fields. Because new fields
     * are creating in reversed order, because of alter after.
     * @param  array  $columns
     * @param  array  $staticNames
     * @return array
     */
    private function getWithoutStaticWithCorrectOrder(array $columns, array $staticNames)
    {
        //Get just field types columns
        $columns = array_filter($columns, function($item) use($staticNames) {
            return !in_array($item->name, $staticNames);
        });

        //Get changed columns with their keys
        $addedColumns = [];
        foreach ($columns as $key => $column) {
            if ( $column->change !== true ) {
                $addedColumns[$key] = $column;
            }
        }

        //Reverse order of only added columns
        foreach ($addedColumns as $key => $value) {
            $columns[$key] = array_values($addedColumns)[array_search($key, array_reverse(array_keys($addedColumns)))];
        }

        return $columns;
    }

    /**
     * Set position in table of static column
     * @param Blueprint $table
     * @param array $enabled
     * @param ColumnDefinition $column
     */
    private function setStaticColumnPosition(Blueprint $table, array $enabled, ColumnDefinition $column)
    {
        $staticNames = array_map(function($class){
            return $class->getColumn();
        }, $enabled);

        //Get index of position
        $staticPosition = array_search($column->name, $staticNames);

        //Created columns without static types
        $withoutStatic = $this->getWithoutStaticWithCorrectOrder($table->getColumns(), $staticNames);

        //Created columns without static types
        $onlyStatic = array_filter($table->getColumns(), function($item) use($staticNames, $column) {
            return in_array($item->name, $staticNames) && $column->name !== $item->name;
        });

        //Add after last column in table builder
        if ( $addAfter = $this->getLastColumnInTable($withoutStatic, $onlyStatic) ){
            $column->after($addAfter->name);
        }
    }
}