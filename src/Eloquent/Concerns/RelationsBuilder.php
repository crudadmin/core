<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;

trait RelationsBuilder
{
    private $saveCollection = null;

    private function getOriginalTableName()
    {
        return Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Returns admin relation key.
     *
     * @param  string  $method
     * @return string
     */
    protected function getAdminRelationKey(string $method)
    {
        return $this->getTable().'.'.$method.'.'.($this->exists ? $this->getKey() : 'global');
    }

    /**
     * Checks if is relation in laravel buffer or in admin buffer.
     *
     * @param  string  $key
     * @return bool
     */
    public function isAdminRelationLoaded(string $key)
    {
        $loaded = parent::relationLoaded($key);

        if (! $loaded) {
            $loaded = AdminCore::get('relations', []);

            return array_key_exists($this->getAdminRelationKey($key), $loaded);
        }

        return $loaded;
    }

    /**
     * Returns relation from cache.
     *
     * @param  string  $key
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function getRelationFromCache(string $key)
    {
        if (parent::relationLoaded($key)) {
            return parent::getRelation($key);
        }

        $relationKey = $this->getAdminRelationKey($key);

        $cache = AdminCore::get('relations');

        //If key exists in cache
        if ( array_key_exists($relationKey, $cache) ){
            return $cache[$relationKey];
        }
    }

    /**
     * Set relation into laravel buffer, and also into admin buffer.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $relationKey = $this->getAdminRelationKey($relation);

        AdminCore::push('relations', $value, $relationKey);

        return parent::setRelation($relation, $value);
    }

    /**
     * Returns relation from cache.
     *
     * @param  string  $method
     * @param  bool  $get
     * @return Illuminate\Database\Eloquent\Relations\Relation|bool
     */
    private function returnRelationFromCache(string $method, $get)
    {
        $relation = $this->getRelationFromCache($method);

        //If is in relation buffer saved admin relation object
        if (is_array($relation) && array_key_exists('type', $relation)) {
            //Returns relationship builder
            if ($get === false || (! $this->exists && ! parent::relationLoaded($method))) {
                //Save old collection when is generating new object
                if ($relation['relation'] instanceof Collection) {
                    $this->saveCollection = $relation['relation'];
                }

                return $this->relationResponse(
                    $method,
                    $relation['type'],
                    $relation['path'],
                    $get === false ? false : true,
                    $relation['properties'],
                    $relation['relation']
                );
            }

            //Returns items from already loaded relationship
            if ($get == true && $relation['get'] == true) {
                if ($relation['relation'] instanceof Collection || $relation['relation'] instanceof BaseModel) {
                    return $relation['relation'];
                } else {
                    return $this->returnRelationItems($relation);
                }
            }
        }

        //If is in relation buffer saved collection and not admin relation object
        else {
            $isCollection = $relation instanceof Collection;

            if ( $get === true ) {
                //If is saved collection or model, and requested is also collection
                if ($isCollection || $relation instanceof BaseModel) {
                    return $relation;
                }
            }

            else {
                //If is saved collection, but requested is object, then save old collection and return new relation object
                if ($isCollection) {
                    $this->saveCollection = $relation;
                }
            }
        }

        return false;
    }

    /**
     * Return relation by belongsToMany field property.
     *
     * @param  string  $method
     * @param  bool  $get
     * @param  bool|array  $models
     * @param  string  $methodSnake
     * @param  string  $methodLowercase
     * @return Illuminate\Database\Eloquent\Relations\Relation|bool
     */
    private function returnByBelongsToMany(string $method, $get, $models, $methodSnake, $methodLowercase)
    {
        if ($this->hasFieldParam($methodSnake, 'belongsToMany')) {
            $properties = $this->getRelationProperty($methodSnake, 'belongsToMany');

            foreach ($models as $path) {
                //Find match
                if (strtolower(Str::snake(class_basename($path))) == $properties[5]) {
                    return $this->relationResponse($methodSnake, 'belongsToMany', $path, $get, $properties);
                }
            }
        }

        return false;
    }

    /**
     * Return relation by belongsTo field property.
     *
     * @param  string  $method
     * @param  bool  $get
     * @param  bool|array  $models
     * @param  string  $methodSnake
     * @return bool|Illuminate\Database\Eloquent\Relations\Relation
     */
    private function returnByBelongsTo(string $method, $get, $models, $methodSnake)
    {
        if ($this->hasFieldParam($methodSnake.'_id', 'belongsTo')) {
            //Get edited field key
            $field_key = $methodSnake.'_id';

            //Get related table
            $foreign_table = explode(',', $this->getFieldParam($field_key, 'belongsTo'))[0];

            foreach ($models as $path) {
                //Find match
                if (Str::snake(class_basename($path)) == str_singular($foreign_table)) {
                    $properties = $this->getRelationProperty($field_key, 'belongsTo');

                    return $this->relationResponse($method, 'belongsTo', $path, $get, $properties);
                }
            }
        }

        return false;
    }

    /**
     * Return relation by belongsToModel property in model.
     * Find all parents in actual model, and check if actual child is not calling some of parents.
     * If yes, then return relationship.
     *
     * @param  string  $method
     * @param  bool  $get
     * @return bool|Illuminate\Database\Eloquent\Relations\Relation
     */
    private function returnByBelongsToModel(string $method, $get)
    {
        foreach ($this->getBelongsToRelation() as $namespace) {
            $basename = class_basename($namespace);

            //If needed method is matched with end of parent model from belongsToModel relation
            if (last(explode('_', snake_case($basename))) == $method) {
                //We want retrieve not class from package, but local class. This class may differ
                //then classname in package's belongsToModel property
                $replacedModel = AdminCore::getModel($basename);

                return $this->relationResponse($method, 'belongsTo', get_class($replacedModel), $get, [
                    4 => $this->getForeignColumn($replacedModel->getTable()),
                ]);
            }
        }

        return false;
    }

    /**
     * Return relations by fields from actual admin model.
     *
     * @param  string  $method
     * @param  bool  $get
     * @param  array|bool  $models
     * @param  string  $methodSnake
     * @param  string  $methodLowercase
     * @return bool|Illuminate\Database\Eloquent\Relations\Relation
     */
    private function returnByFieldsRelations(string $method, $get, $models, $methodSnake, $methodLowercase)
    {
        //Belongs to many relation
        if (($relation = $this->returnByBelongsToMany($method, $get, $models, $methodSnake, $methodLowercase)) !== false) {
            return $relation;
        }

        //Belongs to
        if (($relation = $this->returnByBelongsTo($method, $get, $models, $methodSnake)) !== false) {
            return $relation;
        }

        //Find relation by parent of actual model
        if (($relation = $this->returnByBelongsToModel($method, $get)) !== false) {
            return $relation;
        }

        return false;
    }

    /**
     * Returns relationship for sibling model.
     *
     * @param  string  $method
     * @param  bool  $get
     * @param  bool  $models
     * @return bool|Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function returnAdminRelationship(string $method, $get = false, $models = false)
    {
        $methodLowercase = strtolower($method);
        $methodSnake = Str::snake($method);

        //Checks laravel buffer for relations
        if ($this->isAdminRelationLoaded($method)) {
            if (($cache = $this->returnRelationFromCache($method, $get)) !== false) {
                return $cache;
            }
        }

        //Get all admin modules
        if (! $models) {
            $models = AdminCore::getAdminModelNamespaces();
        }

        /*
         * Return relations by defined fields in actual model
         */
        if (($relation = $this->returnByFieldsRelations($method, $get, $models, $methodSnake, $methodLowercase)) !== false) {
            return $relation;
        }

        $thisBasename = class_basename(get_class($this));
        $thisTableLastPrefix = last(explode('_', snake_case($this->getTable())));

        /*
         * Return relation from other way... search in all models, if some fields or models are note connected with actual model
         */
        foreach ($models as $path) {
            $classname = strtolower(class_basename($path));

            //Find match
            if ($classname == $methodLowercase || str_plural($classname) == $methodLowercase) {
                $model = new $path;

                //If has belongs to many relation
                if (($field = $model->getField($field_key = $this->getTable())) || ($field = $model->getField($field_key = $thisTableLastPrefix))) {
                    if (array_key_exists('belongsToMany', $field)) {
                        $properties = $model->getRelationProperty($field_key, 'belongsToMany');

                        if ($properties[0] == $this->getTable()) {
                            return $this->relationResponse($method, 'manyToMany', $path, $get, $properties);
                        }
                    }
                }

                //Checks all fields in model if has belongsTo relationship
                //if yes, check if called actual model name is same with field key and match relationships
                foreach ($model->getFields() as $key => $field) {
                    if (array_key_exists('belongsTo', $field)) {
                        $properties = $model->getRelationProperty($key, 'belongsTo');

                        if ($properties[0] == $this->getTable()) {
                            $keyLower = trim_end($key, '_id');
                            $keyLower = strtolower(str_replace('_', '', $keyLower));

                            //Check if actual model name is same with property name in singular mode, but compare just last model convention name
                            if (substr(strtolower($thisBasename), -strlen($keyLower)) == $keyLower) {
                                return $this->relationResponse($method, 'hasMany', $path, $get, $properties);
                            }
                        }
                    }
                }

                $modelBelongsToModel = $model->getBelongsToRelation(true);
                $thisBelongsToModel = $this->getBelongsToRelation(true);

                //Check if called model belongs to caller
                if (
                    ! ($isBelongsTo = in_array(class_basename(get_class($model)), $thisBelongsToModel)) &&
                    ! in_array($thisBasename, $modelBelongsToModel)
                ) {
                    break;
                }

                $relationType = $isBelongsTo ? 'belongsTo' : 'hasMany';

                //If relationship can has only one child
                if ($relationType == 'hasMany' && $model->maximum == 1) {
                    $relationType = 'hasOne';
                }

                return $this->relationResponse($method, $relationType, $path, $get, [4 => $this->getForeignColumn($model->getTable())]);
            }
        }

        return false;
    }

    /**
     * Return belongsToModel property in right format.
     *
     * @param  bool  $baseName
     * @return array
     */
    public function getBelongsToRelation($baseName = false)
    {
        $items = array_filter(
            array_wrap($this->getProperty('belongsToModel'))
        );

        if ($baseName !== true) {
            return $items;
        }

        return array_map(function ($item) {
            if ($item) {
                return class_basename($item);
            }
        }, $items);
    }

    /**
     * Returns type of relation.
     *
     * @param  string  $method
     * @param  bool  $relationType
     * @param  string  $path
     * @param  bool  $get
     * @param  array  $properties
     * @return bool|Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function relationResponse(string $method, $relationType, $path, $get = false, $properties = [])
    {
        $relation = false;

        if ($relationType == 'belongsTo') {
            $relation = $this->belongsTo($path, $properties[4]);
        } elseif ($relationType == 'belongsToMany') {
            $relation = $this->belongsToMany($path, $properties[3], $properties[6], $properties[7])->orderBy($properties[3].'.id', 'asc');
        } elseif ($relationType == 'hasOne') {
            $relation = $this->hasOne($path);
        } elseif ($relationType == 'hasMany') {
            $relation = $this->hasMany($path, $properties[4]);
        } elseif ($relationType == 'manyToMany') {
            $relation = $this->belongsToMany($path, $properties[3], $properties[7], $properties[6]);
        }

        if ($relation) {
            $relation_buffer = [
                'relation' => $relation,
                'type' => $relationType,
                'properties' => $properties,
                'path' => $path,
                'get' => $get,
            ];

            //If was relation called as property, and is only hasOne relationship, then return value
            if ($get === true) {
                $relation_buffer['relation'] = $relation = $this->returnRelationItems($relation_buffer) ?: true;
            }

            //Save previous loaded collection into laravel admin buffer
            if ($this->saveCollection !== null) {
                $relation_buffer['relation'] = $this->saveCollection;
                $relation_buffer['get'] = true;

                $this->saveCollection = null;
            }

            $this->setRelation($method, $relation_buffer);
        }

        return $relation;
    }

    /**
     * Returns foreign keys or specific key for parent model.
     *
     * @param  string  $table
     * @return array|string
     */
    public function getForeignColumn($table = null)
    {
        if ($this->getProperty('belongsToModel') == null) {
            return;
        }

        $columns = [];

        foreach (array_wrap($this->getProperty('belongsToModel')) as $parent) {
            $model_table_name = Str::snake(class_basename($parent));

            $columns[str_plural($model_table_name)] = $model_table_name.'_id';
        }

        //Returns
        if ($table) {
            return array_key_exists($table, $columns) ? $columns[$table] : null;
        }

        return $columns;
    }

    /**
     * Returns base model table.
     *
     * @return string
     */
    public function getBaseModelTable()
    {
        return Str::snake(class_basename($this));
    }

    /**
     * Returns properties of field with belongsTo or belongsToMany relationship.
     *
     * @param  string  $key
     * @param  string  $relationType
     *
     * @return array
     */
    public function getRelationProperty(string $key, string $relationType)
    {
        $field = $this->getField($key);

        return $this->getRelationPropertyData($field, $key, $relationType);
    }

    /**
     * Returns properties of field with belongsTo or belongsToMany relationship.
     *
     * @param  array  $field
     * @param  string  $relationType
     *
     * @return array
     */
    public function getRelationPropertyData(array $field, string $key, string $relationType = null)
    {
        $relationType = $relationType ?: (array_key_exists('belongsToMany', $field) ? 'belongsToMany' : 'belongsTo');

        $properties = explode(',', $field[$relationType] ?? '');

        //If is not defined references column for other table
        if (count($properties) == 1) {
            $properties[] = 'NULL';
        }

        if ($relationType == 'belongsToMany') {
            //Table names in singular
            $tables = [
                //We need use original table name, because laravel may change table name with alias for example like
                //"products as laravel_alias_0"... so table relation wont be correct in this case
                str_singular($this->getOriginalTableName()),
                str_singular($properties[0]),
            ];


            //Add pivot table into properties
            $properties[3] = ($properties[2] ?? null) ?: $tables[1].'_'.$tables[0].'_'.$key;
            $properties[4] = $tables[0];
            $properties[5] = $tables[1];
            $properties[6] = $tables[0].'_id';
            $properties[7] = $tables[1].'_id';
            $properties[2] = 'id'; //Reference

            //If is same relationship
            if ( $properties[6] == $properties[7] ){
                $properties[7] = '_'.$properties[7];
            }
        } else {
            if (!($properties[2] ?? null)) {
                $properties[2] = ($properties[2] ?? null) ?: 'id'; //Reference
            }

            $properties[] = str_singular($properties[0]);
            $properties[] = $key;
        }

        return $properties;
    }

    /**
     * Return type of data according to relation type, when is single relation, then method returns model,
     * else returns collection.
     *
     * @param  array  $relation
     * @return mixed
     */
    public function returnRelationItems($relation)
    {
        //If is saved relationship with any result
        if ($relation['relation'] === true) {
            return true;
        }

        return in_array($relation['type'], ['hasOne', 'belongsTo'])
                    ? $relation['relation']->first()
                    : $relation['relation']->get();
    }

    /**
     * If is relation empty, owns TRUE value, so we need return null.
     *
     * @param  mixed  $relation
     * @return null|mixed
     */
    protected function checkIfIsRelationNull($relation)
    {
        return $relation === true ? null : $relation;
    }

    /**
     * Get relation column name or extract additional columns in given format string in format :columnA :columnB.
     *
     * @param  string  $selector
     * @return array
     */
    public function getRelationshipNameBuilder(string $selector)
    {
        preg_match_all('/(?<!\\\\)[\:^]([0-9,a-z,A-Z$_]+)+/', $selector, $matches);

        if (count($matches[1]) == 0) {
            $columns[] = $selector;
        } else {
            $columns = $matches[1];
        }

        return $columns;
    }

    /**
     * Return rows by given model
     *
     * @param  Builder  $query
     * @param  BaseModel  $row
     * @return  [type]
     */
    public function scopeWhereGlobalRelation($query, BaseModel $row)
    {
        $query->where('_table', $row->getTable());
        $query->where('_row_id', $row->getKey());
    }
}
