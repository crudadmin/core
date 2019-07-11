<?php

namespace Admin\Core\Fields\Mutations;

class MutationRule
{
    /**
     * All fields will be passed here in fields generation process.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Actual field of mutation.
     *
     * @var array
     */
    protected $field;

    /**
     * Key name of field.
     *
     * @var string
     */
    protected $key;

    /*
     * Closure with post update mutation.
     *
     * @var Closure(array $fields, array $field, string $key, AdminModel $model)
     */
    protected $postUpdate = null;

    /**
     * Set fields into mutation.
     *
     * @param  array  $field
     * @return void
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Set fields of actual mutation.
     *
     * @param  array  $field
     * @return void
     */
    public function setField(array $field)
    {
        $this->field = $field;
    }


    /**
     * Set key of mutated field.
     *
     * @param  string  $field
     * @return void
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * Get key of mutated field.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set post update event after all mutations will be done.
     *
     * @param  Closure  $closure (array $fields, array $field, string $key, AdminModel $model)
     */
    public function setPostUpdate($closure)
    {
        $this->postUpdate = $closure;
    }

    /**
     * Get post update event.
     *
     * @return Closure|null
     */
    public function getPostUpdate()
    {
        return $this->postUpdate;
    }
}
