<?php

namespace Admin\Core\Contracts\Migrations;

use AdminCore;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Schema;

class MigrationBuilder extends Command
{
    use Concerns\MigrationEvents,
        Concerns\MigrationSupport,
        Concerns\MigrationOutOfDate,
        Concerns\SupportRelations,
        Concerns\SupportColumn,
        Concerns\SupportJson,
        Concerns\HasIndex;

    /*
     * Files
     */
    protected $files;

    public function __construct()
    {
        $this->files = new Filesystem;

        $this->registerMigrationSupport();

        parent::__construct();
    }

    /**
     * Generate CrudAdmin migrations
     * @return void
     */
    protected function migrate($models)
    {
        $migrated = 0;

        foreach ($models as $model)
        {
            $migration = function() use ($model) {
                $this->generateMigration($model);
            };

            //Check if migration is out of date from cache
            if ( $this->isOutOfDate($model, $migration) )
                continue;

            $migrated++;
        }

        if ( $migrated === 0 )
            return $this->line('<info>Noting to migrate.</info>');

        /*
         * Run events migrations from buffer
         */
        foreach ($models as $model)
        {
            $this->fireMigrationEvents($model, 'fire_after_all');
        }
    }

    /**
     * Generate laravel migratons
     * @return void
     */
    protected function generateMigration($model)
    {
        $this->fireModelEvent($model, 'beforeMigrate');

        if ( $model->getSchema()->hasTable( $model->getTable() ) )
        {
            $this->updateTable( $model );
        } else {
            $this->createTable( $model );
        }

        $this->fireModelEvent($model, 'afterMigrate');

        //Checks if model has some extre migrations on create
        $this->registerAfterAllMigrations($model, function($table) use( $model ) {
            $this->fireModelEvent($model, 'onMigrateEnd');
        });

        //Run migrations from cache which have to be runned after actual migration
        $this->fireMigrationEvents($model, 'fire_after_migration');
    }

    /**
     * Skip creating of static columns
     * @param  string     $key
     * @param  AdminModel $model
     * @return bool
     */
    private function skipField(string $key, AdminModel $model)
    {
        $staticColumns = array_map(function($columnClass){
            return $columnClass->getColumn();
        }, $this->getEnabledStaticFields($model));

        return in_array($key, $staticColumns);
    }

    /**
     * Create table from model
     * @return void
     */
    protected function createTable($model)
    {
        $model->getSchema()->create( $model->getTable() , function (Blueprint $table) use ($model) {

            //Increment
            $table->increments('id');

            //Add relationships with other models
            $this->addRelationships($table, $model);

            foreach ($model->getFields() as $key => $value)
            {
                if ( $this->skipField($key, $model) )
                    continue;

                $this->registerColumn($table, $model, $key);
            }

            //Register static columns
            $this->registerStaticColumns($table, $model);
        });

        $this->line('<comment>Created table:</comment> '.$model->getTable());
    }

    /**
     * Update existing table
     * @return void
     */
    protected function updateTable($model)
    {
        $this->line('<info>Updated table:</info> '.$model->getTable());

        $model->getSchema()->table( $model->getTable() , function (Blueprint $table) use ($model) {
            //Add relationships with other models
            $this->addRelationships($table, $model, true);

            //Which columns will be added in reversed order
            $add_columns = [];

            //Which columns has been added, so next columns can not be added after this columns,
            //because this columns are not in database yet
            $except_columns = [];

            foreach ($model->getFields() as $key => $value)
            {
                if ( $this->skipField($key, $model) )
                    continue;

                //Checks if table has column and update it if can...
                if ( $model->getSchema()->hasColumn($model->getTable(), $key) ){
                    if ( $column = $this->registerColumn($table, $model, $key, true) )
                    {
                        $column->change();
                    }
                } else {
                    $except_columns[] = $key;

                    $add_columns[] = [
                        'key' => $key,
                        'callback' => function($except_columns) use ($table, $model, $key, $value){
                            if ( $column = $this->registerColumn($table, $model, $key) )
                            {
                                $previous_column = $this->getPreviousColumn($model, $key, $except_columns);

                                if ( $model->getSchema()->hasColumn($model->getTable(), $previous_column) )
                                    $column->after( $previous_column );

                                //If column does not exists, then add before deleted ad column
                                else if ( $model->getSchema()->hasColumn($model->getTable(), 'deleted_at') )
                                    $column->after( 'id' );
                            }

                            return $column;
                        },
                    ];
                }
            }

            //Add columns in reversed order
            for ( $i = count($add_columns) - 1; $i >= 0; $i-- )
            {
                //if no column has been added, then remove column from array for messages
                if ( !($column = call_user_func_array($add_columns[$i]['callback'], [ $except_columns ])) )
                {
                    unset($add_columns[$i]);
                }
            }

            //Which columns has been successfully added
            foreach ($add_columns as $row)
                $this->line('<comment>+ Added column:</comment> '.$row['key']);

            //Register static columns
            $this->registerStaticColumns($table, $model, true);

            $this->dropUnnecessaryColumns($table, $model);
        });
    }

    /**
     * Automatic columns dropping
     * @param  Blueprint  $table
     * @param  AdminModel $model
     * @return void
     */
    private function dropUnnecessaryColumns(Blueprint $table, AdminModel $model)
    {
        $baseFields = $model->getBaseFields(true);

        $staticColumns = array_map(function($columnClass){
            return $columnClass->getColumn();
        }, $this->getEnabledStaticFields($model));

        //Get enabled static columns
        $baseFields = array_unique(array_merge($baseFields, $staticColumns));

        //Removes unnecessary columns
        foreach ($model->getSchema()->getColumnListing($model->getTable()) as $column)
        {
            if ( ! in_array($column, $baseFields) && ! in_array($column, (array)$model->getProperty('skipDropping')) )
            {
                $this->line('<comment>+ Unknown column:</comment> '.$column);

                $auto_drop = $this->option('auto-drop', false);

                if ( $auto_drop === true || $this->confirm('Do you want drop this column? [y|N]') )
                {
                    if ( $this->hasIndex($model, $column) )
                    {
                        $this->dropIndex($model, $column);
                    }

                    $table->dropColumn($column);

                    $this->line('<comment>+ Dropped column:</comment> '.$column);
                }
            }
        }
    }

    /*
     * Returns field before selected field, if is selected field first, returns last field
     */
    public function getPreviousColumn($model, $findKey, $except = [])
    {
        $last = 'id';
        $i = 0;

        foreach ($model->getFields() as $key => $item)
        {
            if ( $key == $findKey )
            {
                if ( $i == 0 )
                    return 'id';
                else
                    return $last;
            }

            $i++;

            if ( !$model->hasFieldParam($key, 'belongsToMany') && !in_array($key, $except) )
                $last = $key;
        }

        return $last;
    }

    //Returns schema with correct connection
    protected function getSchema($model)
    {
        return Schema::connection( $model->getProperty('connection') );
    }
}