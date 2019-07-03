<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use Illuminate\Support\Facades\DB;
use AdminCore;

trait MigrationRelations
{

    /**
     * Check or add static relationship columns from relation admin models
     * @param object $table
     * @param object $model
     * @param boolena $updating
     */
    public function addRelationships($table, $model, $updating = false)
    {
        $belongsToModel = $model->getBelongsToRelation();

        //Model without belongsToModel parent
        if ( ($count = count($belongsToModel)) == 0 )
            return;

        //If update migration, then go in reverse order
        if ( $updating === true )
            $belongsToModel = array_reverse($belongsToModel);

        foreach ($belongsToModel as $parent)
        {
            $isRecursive = class_basename(get_class($model)) == class_basename($parent);

            //If is recursive model, then do not create new same instance, because of bug when parent model
            //is overidded and has relationship on itself in package with other namespace
            $parent = $isRecursive ? $model : new $parent;

            $foreignColumn = $model->getForeignColumn( $parent->getTable() );

            $column = $table->integer($foreignColumn)->unsigned();

            //If parent belongs to more models, or just itself
            if (
                count($belongsToModel) > 1
                || $model->getProperty('withoutParent') === true
                || $model->getProperty('nullableRelation') === true
                || $isRecursive
            )
                $column->nullable();

            //If foreign key does not exists in table
            if ( ! $model->getSchema()->hasColumn($model->getTable(), $foreignColumn) )
            {
                //If column does not exists in already created table, then create it after id
                if ( $updating === true )
                {
                    $column->after('id');

                    //If is one foreign column, this columns is not null
                    //so if some rows exists, we need push values into this row
                    if ( count($belongsToModel) == 1 && $model->count() > 0 )
                        $this->checkForReferenceTable($model, $foreignColumn, $parent->getTable());

                    $this->line('<comment>+ Added column:</comment> '.$foreignColumn);
                }
            } else if ( $updating === true ) {
                $column->change();
                continue;
            }

            if ( $parent->getConnection() != $model->getConnection() )
            {
                $this->line('<comment>+ Skipped foreign relationship:</comment> '.$foreignColumn . ' <comment>( different db connections )</comment> ');
                continue;
            }

            $this->registerAfterAllMigrations($model, function($table) use ($foreignColumn, $parent) {
                $table->foreign( $foreignColumn )->references( 'id' )->on( $parent->getTable() );
            });
        }
    }


    /**
     * Checks if table has already inserted rows which won't allow insert foreign key without NULL value
     * @param  object $model
     * @param  string $key
     * @param  string $referenceTable
     * @return void
     */
    protected function checkForReferenceTable($model, $key, $referenceTable)
    {
        $this->line('<comment>+ Cannot add foreign key for</comment> <error>'.$key.'</error> <comment>column into</comment> <error>'.$model->getTable().'</error> <comment>table with reference on</comment> <error>'.$referenceTable.'</error> <comment>table.</comment>');
        $this->line('<comment>  Because table has already inserted rows. But you can insert value for existing rows for this</comment> <error>'.$key.'</error> <comment>column.</comment>');

        $referenceTableIds = AdminCore::getModelByTable($referenceTable)->take(10)->select('id')->pluck('id');

        if ( count($referenceTableIds) > 0 )
        {
            $this->line('<comment>+ Here are some ids from '.$referenceTable.' table:</comment> '.implode($referenceTableIds->toArray(), ', '));

            //Define ids for existing rows
            do {
                $requestedId = $this->ask('Which id would you like define for existing rows?');

                if ( !is_numeric($requestedId) )
                    continue;

                if ( AdminCore::getModelByTable($referenceTable)->where('id', $requestedId)->count() == 0 )
                {
                    $this->line('<error>Id #'.$requestedId.' does not exists.</error>');
                    $requestedId = false;
                }
            } while( ! is_numeric($requestedId) );

            //Register event after this migration will be done
            $this->registerAfterMigration($model, function() use ( $model, $key, $requestedId ) {
                DB::connection($model->getConnectionName())->table($model->getTable())->update([ $key => $requestedId ]);
            });
        } else {
            $this->line('<error>+ You have to insert at least one row into '.$referenceTable.' reference table or remove all existing data in actual '.$model->getTable().' table:</error>');
            die;
        }
    }
}