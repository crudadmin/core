<?php

namespace Admin\Core\Contracts\Migrations;

use AdminCore;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Localization;
use Schema;

class MigrationBuilder extends Command
{
    use Concerns\MigrationEvents,
        Concerns\MigrationHelper,
        Concerns\MigrationOutOfDate,
        Concerns\SupportRelations,
        Concerns\SupportSluggable,
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

        $this->registerMigrationHelpers();

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

    /*
     * Skip creating of preddefined columns
     */
    private function skipField($key, $model = null)
    {
        $columns = ['_order', 'created_at', 'published_at', 'updated_at'];

        //When slug is allowed
        if ( $model && $model->getProperty('sluggable') != null )
            $columns[] = 'slug';

        return in_array($key, $columns);
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
                if ( $this->skipField($key) )
                    continue;

                $this->setColumn( $table, $model, $key );

                //Sluggable column
                if ( $model->getProperty('sluggable') != null && $model->getProperty('sluggable') == $key )
                    $this->setSlug( $table, $model );
            }

            //Add multilanguage support
            $this->createLanguageRelationship($table, $model);

            //Order column for sorting rows
            if ( $model->isSortable() )
                $table->integer('_order')->unsigned();

            //Published at column
            if ( $model->getProperty('publishable') == true)
                $table->timestamp('published_at')->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );

            //Softdeletes
            $table->softDeletes();

            //Timestamps
            if ( $model->getProperty('timestamps') == true )
                $table->timestamps();
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
                if ( $this->skipField($key) )
                    continue;

                //Checks if table has column and update it if can...
                if ( $model->getSchema()->hasColumn($model->getTable(), $key) ){
                    if ( $column = $this->setColumn( $table, $model, $key, true ) )
                    {
                        $column->change();
                    }
                } else {
                    $except_columns[] = $key;

                    $add_columns[] = [
                        'key' => $key,
                        'callback' => function($except_columns) use ($table, $model, $key, $value){
                            if ( $column = $this->setColumn( $table, $model, $key ) )
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

            //Add multilanguage support
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'language_id') )
            {
                $this->createLanguageRelationship($table, $model, true);
            }

            //Order column
            if ( ! $model->getSchema()->hasColumn($model->getTable(), '_order') && $model->isSortable() )
            {
                $table->integer('_order')->unsigned();
                $this->line('<comment>+ Added column:</comment> _order');

                //Insert default increment into order
                $this->addDefaultOrder($model);
            }

            //Sluggable column
            if ( $model->getProperty('sluggable') != null )
            {
                if ( ! $model->getSchema()->hasColumn($model->getTable(), 'slug') )
                {
                    $this->setSlug($table, $model, true, true);
                    $this->line('<comment>+ Added column:</comment> slug');
                } else {
                    if ( $setSlug = $this->setSlug($table, $model, true) )
                        $setSlug->change();
                }
            }

            //Published at column
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'published_at') && $model->getProperty('publishable') == true )
            {
                $table->timestamp('published_at')->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );
                $this->line('<comment>+ Added column:</comment> published_at');
            }

            //Deleted at
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'deleted_at') )
            {
                $table->softDeletes();
                $this->line('<comment>+ Added column:</comment> deleted_at');
            }

            /**
             *  Automatic dropping columns
             */
            $base_fields = $model->getBaseFields(true);

            //Removes unneeded columns
            foreach ($model->getSchema()->getColumnListing($model->getTable()) as $column)
            {
                if ( ! in_array($column, $base_fields) && ! in_array($column, (array)$model->getProperty('skipDropping')) )
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
        });
    }

    /*
     * Returns field before selected field, if is selected field first, returns last field
     */
    public function getPreviousColumn($model, $find_key, $except = [])
    {
        $last = 'id';
        $i = 0;

        foreach ($model->getFields() as $key => $item)
        {
            if ( $key == $find_key )
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

    //Resave all rows in model for updating slug if needed
    protected function addDefaultOrder($model)
    {
        $this->registerAfterMigration($model, function() use ($model) {
            $i = 0;

            foreach ($model->get() as $row)
            {
                $row->_order = $i++;
                $row->save();
            }
        });
    }

    /*
     * Add language_id relationship
     */
    protected function createLanguageRelationship($table, $model, $updating = false)
    {
        //If is multi languages support
        if ( ! $model->isEnabledLanguageForeign() )
            return $table;

        $_table = $table->integer('language_id')->unsigned()->nullable();

        if ( $updating == true )
        {
            $_table->after('id');
        }

        $table->foreign('language_id')->references('id')->on('languages');
    }

    //Returns schema with correct connection
    protected function getSchema($model)
    {
        return Schema::connection( $model->getProperty('connection') );
    }
}