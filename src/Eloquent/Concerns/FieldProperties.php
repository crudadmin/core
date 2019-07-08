<?php

namespace Admin\Core\Eloquent\Concerns;

use Fields;
use AdminCore;
use Localization;

trait FieldProperties
{
    /*
     * Buffered fields in model
     */
    private $_fields = null;

    /*
     * Which options can be loaded in getFields (eg data from db)
     */
    private $withOptions = [];

    /*
     * Save admin parent row into model
     */
    protected $withParentRow = null;

    /*
     * Returns just base fields in getAdminAttributes
     */
    protected $justBaseFields = false;

    /*
     * Skip belongsToMany properties in getAdminModelAttributes
     */
    protected $skipBelongsToMany = false;

    /**
     * Return fields converted from string (key:value|otherkey:othervalue) into array format
     * @return [array]
     */
    public function getFields($param = null, $force = false)
    {
        $with_options = count($this->withOptions) > 0;

        if ( $param !== null || $with_options === true )
            $force = true;

        //Field mutations
        if ( $this->_fields == null || $force == true )
        {
            $this->_fields = Fields::getFields( $this, $param, $force );

            $this->withoutOptions();
        }

        return $this->_fields;
    }

    /*
     * Return all model fields with options
     */
    public function getFieldsWithOptions($param = null, $force = false)
    {
        $this->withAllOptions();

        return $this->getFields($param, $force);
    }

    /*
     * Returns needed field
     */
    public function getField($key)
    {
        $fields = $this->getFields();

        if ( array_key_exists($key, $fields) )
            return $fields[$key];

        return null;
    }

    /*
     * Returns type of field
     */
    public function getFieldType($key)
    {
        $field = $this->getField($key);

        return $field['type'];
    }

    /*
     * Check column type
     */
    public function isFieldType($key, $types)
    {
        if ( is_string($types) )
            $types = [ $types ];

        return in_array( $this->getFieldType($key), $types);
    }

    /*
     * Returns maximum length of field
     */
    public function getFieldLength($key)
    {
        $field = $this->getField($key);

        if ( $this->isFieldType($key, ['file', 'password']) )
        {
            return 255;
        }

        //Return maximum defined value
        if ( array_key_exists('max', $field) )
            return $field['max'];

        //Return default maximum value
        return 255;
    }

    /*
     * Returns if field has required
     */
    public function hasFieldParam($key, $params, $paramValue = null)
    {
        if (!$field = $this->getField($key))
            return false;

        foreach (array_wrap($params) as $paramName) {
            if ( array_key_exists($paramName, $field) )
            {
                if ( $paramValue !== null )
                {
                    if ( $field[$paramName] === $paramValue )
                        return true;
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    /*
     * Returns attribute of field
     */
    public function getFieldParam($key, $paramName)
    {
        if ( $this->hasFieldParam($key, $paramName) === false )
            return null;

        $field = $this->getField($key);

        return $field[$paramName];
    }

    /*
     * Field mutator for selects returns all options (also from db, etc...)
     */
    public function withAllOptions()
    {
        return $this->withOptions(true);
    }

    /*
     * Disable generate select options into fields
     */
    public function withoutOptions()
    {
        return $this->withOptions(false);
    }

    /*
     * Allow options
     */
    public function withOptions( $set = null )
    {
        //We want all fields options
        if ( $set === true ){
            $this->withOptions = ['*'];
        }

        //We want specifics fields options
        else if ( is_array($set) || $set === false ){
            $this->withOptions = $set ?: [];
        }

        return $this;
    }

    /*
     * Returns allowed field options
     */
    public function getAllowedOptions()
    {
        return $this->withOptions;
    }

    /*
     * Returns just base fields of model
     */
    public function justBaseFields( $set = null )
    {
        if ( $set === true || $set === false )
            $this->justBaseFields = $set;

        return $this->justBaseFields;
    }

    /*
     * Save admin parent row into model
     */
    public function withModelParentRow($row)
    {
        $this->withParentRow = $row;
    }

    /*
     * Get admin parent row
     */
    public function getModelParentRow()
    {
        return $this->withParentRow;
    }

    /*
     * Return specific value in multi localization field by selected language
     * if translations are missing, returns default, or first available language
     */
    public function returnLocaleValue($object, $lang = null)
    {
        $slug = $lang ?: Localization::get()->slug;

        if ( ! $object || ! is_array($object) )
            return null;

        //If row has saved actual value
        if ( array_key_exists($slug, $object) && (!empty($object[$slug]) || $object[$slug] === 0) ){
            return $object[$slug];
        }

        //Return first available translated value in admin
        foreach ($object as $value) {
            if ( !empty($value) || $value === 0 )
                return $value;
        }

        return null;
    }

    public function getSelectOption($field, $value = null)
    {
        $options = $this->getProperty('options');

        if ( is_null($value) )
            $value = $this->{$field};

        if (
            ! array_key_exists($field, $options)
            || ! array_key_exists($value, $options[$field])
        )
            return null;

        return $options[$field][$value];
    }

    /**
     * Get migration column type
     * @param  string $key
     * @return bool
     */
    private function getMigrationColumnType($key)
    {
        return Fields::getColumnType($this, $key);
    }

    /*
     * Returns short values of fields for content table of rows in administration
     */
    public function getColumnNames()
    {
        $a = AdminCore::cache('models.'.$this->getTable().'.columns_names', function(){
            $fields = ['id'];

            //If has foreign key, add column name to base fields
            if ( $this->getForeignColumn() ) {
                $fields = array_merge($fields, array_values($this->getForeignColumn()));
            }

            foreach ($this->getFields() as $key => $field) {
                $columnType = $this->getMigrationColumnType($key);

                //Skip column types without database column representation
                if ( $columnType && $columnType->hasColumn() )
                    $fields[] = $key;
            }

            //Insert skipped columns
            if ( is_array($this->skipDropping) ) {
                foreach ($this->skipDropping as $key) {
                    $fields[] = $key;
                }
            }

            //Get register static columns from migrations
            //_order, published_at, deleted_at etc...
            $staticColumns = array_map(function($columnClass){
                return $columnClass->getColumn();
            }, Fields::getEnabledStaticFields($this));

            //Get enabled static columns
            $fields = array_unique(array_merge($fields, $staticColumns));

            return $fields;
        });

        return $a;
    }
}