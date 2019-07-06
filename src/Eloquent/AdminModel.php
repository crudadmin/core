<?php

namespace Admin\Core\Eloquent;

use AdminCore;
use Admin\Core\Eloquent\Concerns\FieldProperties;
use Admin\Core\Eloquent\Concerns\HasChildrens;
use Admin\Core\Eloquent\Concerns\HasSettings;
use Admin\Core\Eloquent\Concerns\RelationsBuilder;
use Admin\Core\Eloquent\Concerns\Sluggable;
use Admin\Core\Eloquent\Concerns\Validation;
use Admin\Core\Helpers\File;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Schema;

class AdminModel extends Model
{
    use HasChildrens,
        HasSettings,
        RelationsBuilder,
        FieldProperties,
        Validation,
        Sluggable;

    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = null;

    /*
     * Enable adding new rows
     */
    protected $insertable = true;

    /*
     * Enable updating rows
     */
    protected $editable = true;

    /*
     * Enable deleting rows
     */
    protected $deletable = true;

    /*
     * Enable publishing rows
     */
    protected $publishable = true;

    /*
     * Enable sorting rows
     */
    protected $sortable = true;

    /*
     * Automatic sluggable
     */
    protected $sluggable = null;

    /*
     * Skipping dropping columns into database in migration
     */
    protected $skipDropping = [];

    /*
     * Automatic form and database generation
     */
    protected $fields = [];

    /*
     * Returns also unpublished rows
     */
    public function scopeWithUnpublished($query)
    {
        $query->withoutGlobalScope('publishable');
    }

    public function scopeWithPublished($query)
    {
        $query->where('published_at', '!=', null)->whereRAW('published_at <= NOW()');
    }

    public function __construct(array $attributes = [])
    {
        if ( AdminCore::isLoaded() )
        {
            //Add fillable fields
            $this->makeFillable();

            //Add dates fields
            $this->makeDateable();

            //Add cast attributes
            $this->makeCastable();
        }

        parent::__construct($attributes);
    }

    /*
     * Returns migration date
     */
    public function getMigrationDate()
    {
        if ( !property_exists($this, 'migration_date') )
            return false;

        return $this->migration_date;
    }

    /*
     * On calling method
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __call($method, $parameters)
    {
        //Check if called method is not property, method of actual model or new query model
        if (!method_exists($this, $method) && !$parameters && !method_exists(parent::newQuery(), $method))
        {
            //Checks for db relationship of childrens into actual model
            if ( ($relation = $this->checkForChildrenModels($method)) || ($relation = $this->returnAdminRelationship($method)) )
            {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__call($method, $parameters);
    }

    /*
     * On calling property
     *
     * @see Illuminate\Database\Eloquent\Model
     */
    public function __get($key)
    {
        return $this->getValue($key, false);
    }

    /*
     * Returns modified called property
     */
    public function getValue($key, $force = true)
    {
        $force_check_relation = false;

        // If is called field existing field
        if ( ($field = $this->getField($key)) || ($field = $this->getField($key . '_id')) )
        {
            //Register file type response
            if ( $field['type'] == 'file' && !$this->hasGetMutator($key))
            {
                if ( $file = parent::__get($key) )
                {
                    //If is multilanguage file/s
                    if ( $this->hasFieldParam($key, ['locale'], true) ){
                        $file = $this->returnLocaleValue($file);
                    }

                    if ( is_array($file) || $this->hasFieldParam($key, ['multiple'], true) )
                    {
                        $files = [];

                        if ( !is_array($file) )
                            $file = [ $file ];

                        foreach ($file as $value)
                        {
                            if ( is_string($value) )
                                $files[] = File::adminModelFile($this->getTable(), $key, $value);
                        }

                        return $files;
                    } else {
                        return File::adminModelFile($this->getTable(), $key, $file);
                    }
                }

                return null;
            }

            //Casts time value, because laravel does not casts time
            else if ( $field['type'] == 'time' )
                return ($value = parent::__get($key)) ? Carbon::createFromFormat('H:i:s', $value) : null;

            //If field has not relationship, then return field value... This condition is here for better framework performance
            else if ( !array_key_exists('belongsTo', $field) && !array_key_exists('belongsToMany', $field) || substr($key, -3) == '_id' ){

                if ( array_key_exists('locale', $field) && $field['locale'] === true ) {
                    $object = parent::__get($key);

                    return $this->returnLocaleValue($object);
                }

                return parent::__get($key);
            } else {
                $force_check_relation = true;
            }
        }

        // Register this offen called properties for better performance
        else if ( in_array($key, ['id', 'slug', 'created_at', 'published_at', 'deleted_at', 'pivot']) ) {
            if ( $key != 'slug' || $this->sluggable == true && $key == 'slug' )
                return parent::__get($key);
        }

        //If is fields called from outside of class, then try to search relationship
        if ( in_array($key, ['fields']) || $force == true )
        {
            $force_check_relation = true;
        }

        // Checks for relationship
        if ($force_check_relation === true || !property_exists($this, $key) && !method_exists($this, $key) && !array_key_exists($key, $this->attributes) && !$this->hasGetMutator($key) )
        {
            //If relations has been in buffer, but returns nullable value
            if ( $relation = $this->returnAdminRelationship($key, true) )
            {
                return $this->checkIfIsRelationNull($relation);
            }

            //Checks for db relationship childrens into actual model
            else if ( $relation = $this->checkForChildrenModels($key, true) ) {
                return $this->checkIfIsRelationNull($relation);
            }
        }

        return parent::__get($key);
    }

    /**
     * Set fillable property for laravel model from admin fields
     */
    protected function makeFillable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            //Skip column
            if ( array_key_exists('belongsToMany', $field) )
                continue;

            $this->fillable[] = $key;
        }

        //Add published_at property
        $this->fillable[] = 'published_at';

        //If has relationship, then allow foreign key
        if ( $this->belongsToModel != null )
        {
            $this->fillable = array_merge(array_values($this->getForeignColumn()), $this->fillable);
        }

        //If is moddel sluggable
        if ( $this->sluggable != null )
            $this->fillable[] = 'slug';
    }

    /*
     * Set date fields
     */
    protected function makeDateable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            if ( $this->isFieldType($key, ['date', 'datetime']) && ! $this->hasFieldParam($key, ['multiple', 'locale'], true) )
                $this->dates[] = $key;
        }

        //Add dates
        $this->dates[] = 'published_at';
    }


    /*
     * Set selectbox field to automatic json format
     */
    protected function makeCastable()
    {
        foreach ($this->getFields() as $key => $field)
        {
            //Add cast attribute for fields with multiple select
            if ( (
                     $this->isFieldType($key, ['select', 'file', 'date', 'time'])
                     && $this->hasFieldParam($key, 'multiple', true)
                 )
                 || $this->isFieldType($key, 'json')
                 || $this->hasFieldParam($key, 'locale')
             )
                $this->casts[$key] = 'json';

            else if ( $this->isFieldType($key, 'checkbox') )
                $this->casts[$key] = 'boolean';

            else if ( $this->isFieldType($key, 'integer') || $this->hasFieldParam($key, 'belongsTo') )
                $this->casts[$key] = 'integer';

            else if ( $this->isFieldType($key, 'decimal') )
                $this->casts[$key] = 'float';

            else if ( $this->isFieldType($key, ['date']) )
                $this->casts[$key] = 'date';

            else if ( $this->isFieldType($key, ['datetime']) )
                $this->casts[$key] = 'datetime';
        }

        //Casts foreign keys
        if ( is_array($relations = $this->getForeignColumn()) )
        {
            foreach ($relations as $key)
                $this->casts[$key] = 'integer';
        }
    }

    /**
     * Set inside property
     * @param string $property
     * @param mixed $value
     */
    public function setProperty($property, $value)
    {
        $this->{$property} = $value;

        return $this;
    }

    /*
     * Returns property
     */
    public function getProperty($property, $row = null)
    {
        //Translates
        if ( in_array($property, ['name', 'title']) && $translate = trans($this->{$property}) )
            return $translate;

        //Object / Array
        elseif (in_array($property, ['fields', 'active', 'options', 'settings', 'buttons', 'reserved', 'insertable', 'editable', 'deletable', 'layouts', 'belongsToModel'])) {

            if ( method_exists($this, $property) )
                return $this->{$property}($row);

            if ( property_exists($this, $property) )
                return $this->{$property};

            return null;
        }

        return $this->{$property};
    }

    //Returns schema with correct connection
    public function getSchema()
    {
        return Schema::connection( $this->getProperty('connection') );
    }
}