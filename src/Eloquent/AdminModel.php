<?php

namespace Admin\Core\Eloquent;

use AdminCore;
use Admin\Core\Eloquent\Concerns\AdminModelFieldValue;
use Admin\Core\Eloquent\Concerns\BootAdminModel;
use Admin\Core\Eloquent\Concerns\FieldModules;
use Admin\Core\Eloquent\Concerns\FieldProperties;
use Admin\Core\Eloquent\Concerns\HasChildrens;
use Admin\Core\Eloquent\Concerns\HasProperties;
use Admin\Core\Eloquent\Concerns\HasSettings;
use Admin\Core\Eloquent\Concerns\RelationsBuilder;
use Admin\Core\Eloquent\Concerns\Sluggable;
use Admin\Core\Eloquent\Concerns\Validation;
use Admin\Core\Helpers\File;
use Carbon\Carbon;
use Fields;
use Illuminate\Database\Eloquent\Model;
use Schema;

class AdminModel extends Model
{
    use BootAdminModel,
        HasChildrens,
        HasSettings,
        HasProperties,
        RelationsBuilder,
        FieldProperties,
        FieldModules,
        Validation,
        Sluggable;

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
     * Returns also unpublished rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeWithUnpublished($query)
    {
        $query->withoutGlobalScope('publishable');
    }

    /**
     * Returns only published rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeWithPublished($query)
    {
        $query->where($this->getTable().'.published_at', '!=', null)
              ->whereRAW($this->getTable().'.published_at <= NOW()');
    }

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
     * On calling method.
     * @see Illuminate\Database\Eloquent\Model
     *
     * @param  string  $method
     * @param  mixed  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //Check if called method is not property, method of actual model or new query model
        if (! method_exists($this, $method) && ! $parameters && ! method_exists(parent::newQuery(), $method)) {
            //Checks for db relationship of childrens into actual model
            if (($relation = $this->checkForChildrenModels($method)) || ($relation = $this->returnAdminRelationship($method))) {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * On calling property.
     *
     * @see Illuminate\Database\Eloquent\Model
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getValue($key, false);
    }

    /**
     * Returns modified called property.
     *
     * @param string  $key
     * @param  bool  $force
     * @return mixed
     */
    public function getValue($key, $force = true)
    {
        $forceCheckRelation = false;

        // If is called field existing field
        if (($field = $this->getField($key)) || ($field = $this->getField($key.'_id'))) {
            //Register file type response
            if ($field['type'] == 'file' && ! $this->hasGetMutator($key)) {
                if ($file = parent::__get($key)) {
                    //If is multilanguage file/s
                    if ($this->hasFieldParam($key, ['locale'], true)) {
                        $file = $this->returnLocaleValue($file);
                    }

                    if (is_array($file) || $this->hasFieldParam($key, ['multiple'], true)) {
                        $files = [];

                        if (! is_array($file)) {
                            $file = [$file];
                        }

                        foreach ($file as $value) {
                            if (is_string($value)) {
                                $files[] = File::adminModelFile($this->getTable(), $key, $value, $this->getKey());
                            }
                        }

                        return $files;
                    } else {
                        return File::adminModelFile($this->getTable(), $key, $file, $this->getKey());
                    }
                }

                return;
            }

            //Casts time value, because laravel does not casts time
            elseif ($field['type'] == 'time') {
                return ($value = parent::__get($key)) ? Carbon::createFromFormat('H:i:s', $value) : null;
            }

            else if ( in_array($field['type'], ['editor', 'longeditor']) ) {
                $value = parent::__get($key);

                if ($this->hasFieldParam($key, ['locale'], true)) {
                    $value = $this->returnLocaleValue($value);
                }

                if ( $value && class_exists('Admin') && \Admin::isFrontend() ) {
                    if ( \FrontendEditor::isActive() && admin()->hasAccess($this, 'update') ) {
                        $hash = \FrontendEditor::makeHash($this->getTable(), $key, $this->getKey());

                        $attributes = 'data-model="'.$this->getTable().'" data-key="'.$key.'" data-id="'.$this->getKey().'" data-hash="'.$hash.'"';
                    }

                    return '<div data-crudadmin-editor'.(isset($attributes) ? (' '.$attributes) : '').'>'.$value.'</div>';
                }

                return $value;
            }

            //Register custom global module mutators
            else if ( ($value = $this->getModuleFieldValue($key, $field)) && $value && $value instanceof AdminModelFieldValue ) {
                return $value->getValue();
            }

            //If field has not relationship, then return field value... This condition is here for better framework performance
            elseif (! array_key_exists('belongsTo', $field) && ! array_key_exists('belongsToMany', $field) || substr($key, -3) == '_id') {
                //Does not allow get specific locale value from localized slug column
                if (array_key_exists('locale', $field) && $field['locale'] === true && $key != 'slug' ) {
                    $object = parent::__get($key);

                    return $this->returnLocaleValue($object);
                }

                return parent::__get($key);
            } else {
                $forceCheckRelation = true;
            }
        }

        // Register this offen called properties for better performance
        elseif (in_array($key, ['id', 'slug', 'created_at', 'published_at', 'deleted_at', 'pivot'])) {
            if ($key != 'slug' || $this->sluggable == true && $key == 'slug') {
                return parent::__get($key);
            }
        }

        //If is fields called from outside of class, then try to search relationship
        if (in_array($key, ['fields']) || $force == true) {
            $forceCheckRelation = true;
        }

        // Checks for relationship
        if ($forceCheckRelation === true || ! property_exists($this, $key) && ! method_exists($this, $key) && ! array_key_exists($key, $this->attributes) && ! $this->hasGetMutator($key)) {
            //If relations has been in buffer, but returns nullable value
            if ($relation = $this->returnAdminRelationship($key, true)) {
                return $this->checkIfIsRelationNull($relation);
            }

            //Checks for db relationship childrens into actual model
            elseif ($relation = $this->checkForChildrenModels($key, true)) {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__get($key);
    }

    private function getModuleFieldValue($key, $field)
    {
        $returnResponse = null;

        $value = parent::__get($key);

        $this->runAdminModules(function($module) use (&$returnResponse, $key, $field, &$value) {
            if ( method_exists($module, 'fieldValue') ) {
                $response = $module->fieldValue($this, $key, $field, $value);

                if ( $response && $response instanceof AdminModelFieldValue ){
                    //Rewrite previous value, to support multiple responses at once
                    $value = $response->getValue();

                    $returnResponse = $response;
                }
            }
        });

        return $returnResponse;
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
     * Set date fields.
     *
     * @return void
     */
    protected function makeDateable()
    {
        foreach ($this->getFields() as $key => $field) {
            if ($this->isFieldType($key, ['date', 'datetime']) && ! $this->hasFieldParam($key, ['multiple', 'locale'], true)) {
                $this->dates[] = $key;
            }
        }

        //Add dates
        $this->dates[] = 'published_at';
    }

    /**
     * Set selectbox field to automatic json format.
     *
     * @return void
     */
    protected function makeCastable()
    {
        foreach ($this->getFields() as $key => $field) {
            //Add cast attribute for fields with multiple select
            if (
                (
                     $this->isFieldType($key, ['select', 'file', 'date', 'time'])
                     && $this->hasFieldParam($key, 'multiple', true)
                 )
                 || $this->isFieldType($key, 'json')
                 || $this->hasFieldParam($key, 'locale')
             ) {
                $this->casts[$key] = 'json';
            } elseif ($this->isFieldType($key, 'checkbox')) {
                $this->casts[$key] = 'boolean';
            } elseif ($this->isFieldType($key, 'integer') || $this->hasFieldParam($key, 'belongsTo')) {
                $this->casts[$key] = 'integer';
            } elseif ($this->isFieldType($key, 'decimal')) {
                $this->casts[$key] = 'float';
            } elseif ($this->isFieldType($key, ['date'])) {
                $this->casts[$key] = 'date';
            } elseif ($this->isFieldType($key, ['datetime'])) {
                $this->casts[$key] = 'datetime';
            }
        }

        //Casts foreign keys
        if (is_array($relations = $this->getForeignColumn())) {
            foreach ($relations as $key) {
                $this->casts[$key] = 'integer';
            }
        }
    }

    /**
     * Set inside property.
     *
     * @param  string  $property
     * @param  mixed  $value
     *
     * @return $this
     */
    public function setProperty($property, $value)
    {
        $this->{$property} = $value;

        return $this;
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
