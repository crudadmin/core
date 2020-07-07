<?php

namespace Admin\Core\Fields;

use BadMethodCallException;

class Group
{
    /*
     * Id of group
     */
    public $id = null;

    /*
     * Group name
     */
    public $name = null;

    /*
     * Fields list
     */
    public $fields = [];

    /*
     * What parameters should be added
     */
    public $add = [];

    /*
     * Group type
     */
    public $type = 'default';

    /**
     * Prefix for all columns
     *
     * @var  string|null
     */
    public $prefix = null;

    /*
     * Forward methods to support nonstatic/stattic...
     * origMethodName => alias
     */
    protected $forwardCalls = [];

    /*
     * Boot group and add fields into class
     */
    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * Set id of group.
     * @param  string $id
     * @return Group
     */
    public function id(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name of group.
     * @param  $name
     * @return Group
     */
    public function name($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Make fields group.
     * @param  array  $fields
     * @param  string $type
     * @return Group
     */
    public static function fields(array $fields = [], $type = 'default')
    {
        return (new static($fields))->type($type);
    }

    /**
     * Add fields into group.
     *
     * @param  array  $fields
     * @return Group
     */
    public function pushFields(array $fields = [])
    {
        $this->fields = array_merge($this->fields ?: [], $fields);

        return $this;
    }

    /**
     * Push fields parameters into every field in group.
     * @param string $params
     * @return Group
     */
    public function add($params)
    {
        $this->add[] = $params;

        return $this;
    }

    /**
     * Push fields into given group
     *
     * @param  array  $fields
     * @return  Group
     */
    public function push($fields)
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
     * Push fields into given before all fields
     *
     * @param  array  $fields
     * @return  Group
     */
    public function pushBefore($fields)
    {
        $this->fields = array_merge($fields, $this->fields);

        return $this;
    }

    /**
     * Set type of group.
     * @param $type string group/tab
     * @return Group
     */
    public function type($type = 'group')
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set prefix for all columns
     *
     * @param  string  $prefix
     * @return  Group
     */
    public function prefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Handle dynamic method calls into the model.
     * And forawd some methods
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters) {
        foreach ($this->forwardCalls ?: [] as $originalName => $alias) {
            if ( $originalName == $method ){
                return $this->{$alias}(...$parameters);
            }
        }

        if ( method_exists($this, $method) ) {
            return $this->$method(...$parameters);
        } else {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, $method
            ));
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        return (new static)->$method(...$parameters);
    }
}
