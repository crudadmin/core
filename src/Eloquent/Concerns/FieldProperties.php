<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;
use Admin\Core\Eloquent\AdminModel;
use Fields;
use Localization;

trait FieldProperties
{
    /**
     * Buffered fields in model.
     *
     * @var null|array
     */
    private $_fields = null;

    /**
     * Which options can be loaded in getFields (eg data from db).
     *
     * @var array
     */
    private $withOptions = [];

    /**
     * Save admin parent row into model.
     *
     * @var Admin\Core\Eloquent\AdminModel|null
     */
    protected $withParentRow = null;

    /**
     * Returns just base fields in getAdminAttributes.
     *
     * @var bool
     */
    protected $justBaseFields = false;

    /**
     * Skip belongsToMany properties in getAdminModelAttributes.
     *
     * @var bool
     */
    protected $skipBelongsToMany = false;

    /**
     * Return fields converted from string (key:value|otherkey:othervalue) into array format.
     *
     * @param  Admin\Core\Eloquent\AdminModel|null  $param
     * @param  bool  $force
     * @return array
     */
    public function getFields($param = null, $force = false)
    {
        $with_options = count($this->withOptions) > 0;

        if ($param !== null || $with_options === true) {
            $force = true;
        }

        //Field mutations
        if ($this->_fields == null || $force == true) {
            $this->_fields = Fields::getFields($this, $param, $force);

            $this->withoutOptions();
        }

        return $this->_fields;
    }

    /**
     * Return all model fields with options.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $param
     * @param  bool  $force
     * @return array
     */
    public function getFieldsWithOptions($param = null, $force = false)
    {
        $this->withAllOptions();

        return $this->getFields($param, $force);
    }

    /**
     * Returns needed field.
     *
     * @param  string $key
     * @return array|null
     */
    public function getField(string $key)
    {
        $fields = $this->getFields();

        if (array_key_exists($key, $fields)) {
            return $fields[$key];
        }

        return null;
    }

    /**
     * Returns type of field.
     *
     * @param  string  $key
     * @return string
     */
    public function getFieldType(string $key)
    {
        $field = $this->getField($key);

        return $field['type'];
    }

    /**
     * Check column type.
     *
     * @param  string  $key
     * @param  string|array  $types
     * @return bool
     */
    public function isFieldType(string $key, $types)
    {
        if (is_string($types)) {
            $types = [ $types ];
        }

        return in_array($this->getFieldType($key), $types);
    }

    /**
     * Returns maximum length of field.
     *
     * @param  string  $key
     * @return int
     */
    public function getFieldLength(string $key)
    {
        $field = $this->getField($key);

        if ($this->isFieldType($key, ['file', 'password'])) {
            return 255;
        }

        //Return maximum defined value
        if (array_key_exists('max', $field)) {
            return $field['max'];
        }

        //Return default maximum value
        return 255;
    }

    /**
     * Returns if field has required.
     *
     * @param  string  $key
     * @param  string|array  $params
     * @param  mixed  $paramValue
     * @return bool
     */
    public function hasFieldParam(string $key, $params, $paramValue = null)
    {
        if (!$field = $this->getField($key)) {
            return false;
        }

        foreach (array_wrap($params) as $paramName) {
            if (array_key_exists($paramName, $field)) {
                if ($paramValue !== null) {
                    if ($field[$paramName] === $paramValue) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns attribute of field.
     *
     * @param  string  $key
     * @param  string  $paramName
     * @return mixed
     */
    public function getFieldParam(string $key, string $paramName)
    {
        if ($this->hasFieldParam($key, $paramName) === false) {
            return null;
        }

        $field = $this->getField($key);

        return $field[$paramName];
    }

    /**
     * Field mutator for selects returns all options (also from db, etc...).
     *
     * @return $this
     */
    public function withAllOptions()
    {
        return $this->withOptions(true);
    }

    /**
     * Disable generate select options into fields.
     *
     * @return $this
     */
    public function withoutOptions()
    {
        return $this->withOptions(false);
    }

    /**
     * Allow options.
     *
     * @param  bool|array  $set
     * @return $this
     */
    public function withOptions($set = null)
    {
        //We want all fields options
        if ($set === true) {
            $this->withOptions = ['*'];
        }

        //We want specifics fields options
        elseif (is_array($set) || $set === false) {
            $this->withOptions = $set ?: [];
        }

        return $this;
    }

    /**
     * Returns allowed field options.
     *
     * @return array
     */
    public function getAllowedOptions()
    {
        return $this->withOptions;
    }

    /**
     * Returns just base fields of model.
     *
     * @param  bool|null  $set
     * @return bool
     */
    public function justBaseFields($set = null)
    {
        if ($set === true || $set === false) {
            $this->justBaseFields = $set;
        }

        return $this->justBaseFields;
    }

    /**
     * Save admin parent row into model.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $row
     * @return void
     */
    public function withModelParentRow(AdminModel $row)
    {
        $this->withParentRow = $row;
    }

    /**
     * Get admin parent row.
     *
     * @return Admin\Core\Eloquent\AdminModel|null
     */
    public function getModelParentRow()
    {
        return $this->withParentRow;
    }

    /**
     * Return specific value in multi localization field by selected language
     * if translations are missing, returns default, or first available language.
     *
     * @param  mixed  $object
     * @param  string|null  $lang
     * @return mixed
     */
    public function returnLocaleValue($object, $lang = null)
    {
        $slug = $lang ?: Localization::get()->slug;

        if (! $object || ! is_array($object)) {
            return null;
        }

        //If row has saved actual value
        if (array_key_exists($slug, $object) && (!empty($object[$slug]) || $object[$slug] === 0)) {
            return $object[$slug];
        }

        //Return first available translated value in admin
        foreach ($object as $value) {
            if (!empty($value) || $value === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns value of given key from options.
     *
     * @param  string  $field
     * @param  string|int  $value
     * @return string
     */
    public function getSelectOption(string $field, $value = null)
    {
        $options = $this->getProperty('options');

        if (is_null($value)) {
            $value = $this->{$field};
        }

        if (
            ! array_key_exists($field, $options)
            || ! array_key_exists($value, $options[$field])
        ) {
            return null;
        }

        return $options[$field][$value];
    }

    /**
     * Get migration column type.
     *
     * @param  string  $key
     * @return bool
     */
    private function getMigrationColumnType($key)
    {
        return Fields::getColumnType($this, $key);
    }

    /**
     * Returns short values of fields for content table of rows in administration.
     *
     * @return array
     */
    public function getColumnNames()
    {
        return Fields::cache('models.'.$this->getTable().'.columns_names', function () {
            $fields = ['id'];

            //If has foreign key, add column name to base fields
            if ($this->getForeignColumn()) {
                $fields = array_merge($fields, array_values($this->getForeignColumn()));
            }

            foreach ($this->getFields() as $key => $field) {
                $columnType = $this->getMigrationColumnType($key);

                //Skip column types without database column representation
                if ($columnType && $columnType->hasColumn()) {
                    $fields[] = $key;
                }
            }

            //Insert skipped columns
            if (is_array($this->skipDropping)) {
                foreach ($this->skipDropping as $key) {
                    $fields[] = $key;
                }
            }

            //Get register static columns from migrations
            //_order, published_at, deleted_at etc...
            $staticColumns = array_map(function ($columnClass) {
                return $columnClass->getColumn();
            }, Fields::getEnabledStaticFields($this));

            //Get enabled static columns
            $fields = array_unique(array_merge($fields, $staticColumns));

            return $fields;
        });
    }
}
