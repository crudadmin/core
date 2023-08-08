<?php

namespace Admin\Core\Eloquent;

use AdminCore;
use Admin\Core\Eloquent\Concerns\BootAdminModel;
use Admin\Core\Eloquent\Concerns\FieldModules;
use Admin\Core\Eloquent\Concerns\FieldProperties;
use Admin\Core\Eloquent\Concerns\HasAdminCasts;
use Admin\Core\Eloquent\Concerns\HasChildrens;
use Admin\Core\Eloquent\Concerns\HasLocalizedValues;
use Admin\Core\Eloquent\Concerns\HasProperties;
use Admin\Core\Eloquent\Concerns\HasPublishable;
use Admin\Core\Eloquent\Concerns\HasSettings;
use Admin\Core\Eloquent\Concerns\HasStorage;
use Admin\Core\Eloquent\Concerns\RelationsBuilder;
use Admin\Core\Eloquent\Concerns\RelationsMapBuilder;
use Admin\Core\Eloquent\Concerns\Sluggable;
use Admin\Core\Eloquent\Concerns\Uploadable;
use Admin\Core\Eloquent\Concerns\Validation;
use Admin\Core\Helpers\File;
use Carbon\Carbon;
use Fields;
use Illuminate\Database\Eloquent\Model;
use Schema;

class AdminModel extends Model
{
    use BootAdminModel,
        HasAdminCasts,
        HasChildrens,
        HasSettings,
        HasProperties,
        RelationsBuilder,
        RelationsMapBuilder,
        FieldProperties,
        FieldModules,
        Validation,
        Sluggable,
        HasStorage,
        Uploadable,
        HasLocalizedValues,
        HasPublishable;

    /**
     * Model Parent
     * Eg. Articles::class,.
     *
     * @var  string
     */
    protected $belongsToModel = null;

    /**
     * Enable adding new rows.
     *
     * @var  bool
     */
    protected $insertable = true;

    /**
     * Enable updating rows.
     *
     * @var  bool
     */
    protected $editable = true;

    /**
     * Enable deleting rows.
     *
     * @var  bool
     */
    protected $deletable = true;

    /**
     * Enable publishing rows.
     *
     * @var  bool
     */
    protected $publishable = true;

    /**
     * Enable enhanced publishable features
     *
     * @var  bool
     */
    protected $publishableState = false;

    /**
     * Enable sorting rows.
     *
     * @var  bool
     */
    protected $sortable = true;

    /**
     * Automatic sluggable.
     *
     * @var  string|null
     */
    protected $sluggable = null;

    /**
     * Skipping dropping columns into database in migration.
     *
     * @var  array
     */
    protected $skipDropping = [];

    /**
     * Automatic form and database generation.
     *
     * @var  array
     */
    protected $fields = [];

    /**
     * Model modules
     *
     * @var  array
     */
    protected $modules = [];

    /**
     * Admin model properties of booted model
     *
     * @var  array
     */
    static $adminBooted = [];

    /**
     * Create a new Admin Eloquent instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        if (AdminCore::isLoaded()) {
            $this->cachableFieldsProperties(function(){
                //Add fillable fields
                $this->makeFillable();

                //Add dates fields
                $this->makeDateable();

                //Add cast attributes
                $this->makeCastable();

                //Boot all admin eloquent modules
                $this->bootAdminModules();

                //Register relationships tree
                $this->bootRelationships();
            });

            $this->bootCachableProperties();
        }

        parent::__construct($attributes);
    }

    /**
     * Mutate fields method for parent::mutateFields() support
     *
     * @param  Admin\Tests\App\Models\Fields\FieldsMutator  $fields
     * @param  AdminModel||null  $params
     *
     * @return void
     */
    public function mutateFields($fields)
    {
        //...
    }

    /**
     * Returns migration date.
     *
     * @return string|bool
     */
    public function getMigrationDate()
    {
        if (! property_exists($this, 'migration_date')) {
            return false;
        }

        return $this->migration_date;
    }

    /**
     * Get table performance improvement with caching table name
     *
     * @return  string
     */
    public function getTable()
    {
        if ( !$this->table ){
            $this->table = parent::getTable();
        }

        return $this->table;
    }

    /**
     * DEPREACED:
     * Returns modified called property.
     *
     * @param string  $key
     * @param  bool  $force
     * @return mixed
     */
    public function getValue($key, $force = true)
    {
        // If is called field existing field
        // TODO: refactor gutenberg
        // if (($field = $this->getField($key))) {
            // if ( in_array($field['type'], ['editor', 'longeditor']) ) {
            //     $value = $this->getParentValue($key);

            //     if ($this->hasFieldParam($key, ['locale'], true)) {
            //         $value = $this->getLocaleValue($value);
            //     }

            //     if ( $value && class_exists('Admin') && \Admin::isFrontend() ) {
            //         if ( \FrontendEditor::isActive() && admin()->hasAccess($this, 'update') ) {
            //             $hash = \FrontendEditor::makeHash($this->getTable(), $key, $this->getKey());

            //             $attributes = 'data-model="'.$this->getTable().'" data-key="'.$key.'" data-id="'.$this->getKey().'" data-hash="'.$hash.'"';
            //         }

            //         return '<div data-crudadmin-editor'.(isset($attributes) ? (' '.$attributes) : '').'>'.$value.'</div>';
            //     }

            //     return $value;
            // }
        // }

        return parent::__get($key);
    }

    /**
     * Set fillable property for laravel model from admin fields.
     *
     * @return void
     */
    protected function makeFillable()
    {
        foreach ($this->getFields() as $key => $field) {
            //Skip column
            if (! ($column = Fields::getColumnType($this, $key)) || ! $column->hasColumn()) {
                continue;
            }

            $this->fillable[] = $key;
        }

        //Add published_at property
        if ($this->publishable) {
            $this->fillable[] = 'published_at';
        }

        //If has relationship, then allow foreign key
        if ($this->getProperty('belongsToModel') != null) {
            $this->fillable = array_merge(array_values($this->getForeignColumn()), $this->fillable);
        }

        //If is moddel sluggable
        if ($this->sluggable != null) {
            $this->fillable[] = 'slug';
        }
    }

    /**
     * Returns schema with correct connection.
     *
     * @return  Illuminate\Support\Facades\Schema
     */
    public function getSchema()
    {
        return Schema::connection($this->getProperty('connection'));
    }

    /**
     * Fix ambiguous column or multiple columns
     *
     * @param  string/array  $columns
     * @param  string        $table
     * @return  string/array
     */
    public function fixAmbiguousColumn($column, $table = null)
    {
        if ( ! $table ) {
            $table = $this->getTable();
        }

        if ( is_array($column) ) {
            return array_map(function($item) use ($table) {
                return $this->fixAmbiguousColumn($item, $table);
            }, $column);
        }

        if ( strpos($column, '.') === false ) {
            return $this->getTable().'.'.$column;
        }

        return $column;
    }
}
