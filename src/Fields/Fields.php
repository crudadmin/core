<?php

namespace Admin\Core\Fields;

use Admin\Core\Contracts\DataStore;
use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Fields\Concerns\FieldTypes;
use Admin\Core\Fields\Concerns\HasMutations;
use Admin\Core\Fields\Concerns\StaticFields;
use Admin\Core\Fields\Concerns\HasAttributes;
use Admin\Core\Fields\Mutations\MutationRule;
use Admin\Core\Migrations\Concerns\MigrationDefinition;

class Fields extends MigrationDefinition
{
    use FieldTypes,
        StaticFields,
        HasMutations,
        HasAttributes,
        DataStore;

    /**
     * Model fields.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Model fields without options.
     *
     * @var array
     */
    protected $base_fields = [];

    /**
     * Loaded models completelly.
     *
     * @var array
     */
    protected $loaded_fields = [];

    /**
     * Model groups of fields.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Fields which will be removed.
     *
     * @var array
     */
    protected $remove = [];

    /**
     * Field mutator.
     *
     * @var array
     */
    protected $mutationBuilder = [];

    /**
     * Update fields after rendering all attributes.
     *
     * @var array
     */
    protected $post_update = [];

    /**
     * Returns loaded column class.
     *
     * @param  string|object $columnClass
     * @return Admin\Core\Migrations\Concerns\MigrationDefinition
     */
    private function bootColumnClass($columnClass)
    {
        if (is_string($columnClass)) {
            $columnClass = new $columnClass;
        }

        //Set class input and output for interaction support
        $columnClass->setCommand($this->getCommand());

        return $columnClass;
    }

    /**
     * Checks if key of field is key for group fields.
     *
     * @param  string|array|Admin\Core\Fields\Group  $field
     * @return bool
     */
    protected function isFieldGroup($field)
    {
        if (is_string($field)) {
            return false;
        }

        if ($field instanceof Group) {
            return $field;
        }

        return false;
    }

    /**
     * Push additional parameters into field from group.
     *
     * @param  array  $field
     * @param  array  $add
     * @return array
     */
    protected function pushParams($field, array $add)
    {
        foreach ($add as $params) {
            $field = (new Mutations\FieldToArray)->update($field) + (new Mutations\FieldToArray)->update($params);
        }

        return $field;
    }

    /**
     * Returns all fields of model.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @param  Admin\Core\Eloquent\AdminModel|null  $param
     * @param  bool  $force
     * @return array
     */
    public function getFields($model, $param = null, $force = true)
    {
        //Get model table name
        $table = $model->getTable();

        //Return fields from cache
        if (
            array_key_exists($table, $this->base_fields)
            && $this->isCompletedState($table)
            && $force === false
        ) {
            return $this->base_fields[$table];
        }

        $this->setUncompletedState($table);

        //Resets buffer
        $this->fields[$table] = [];
        $this->groups[$table] = [];
        $this->remove[$table] = [];
        $this->post_update[$table] = [];
        $this->mutationBuilder[$table] = null;

        //Fields from model
        $fields = $model->getProperty('fields', $param);

        //Put fields into group, if are represented as array
        $fields = is_array($fields) ? new Group($fields) : $fields;

        //Get actual model mutation
        $this->mutationBuilder[$table] = $this->addFieldsMutationIntoModel($model, $param);

        //Register fields from groups
        $this->manageGroupFields($model, 0, $fields, null);

        //Set rendering of fields as completed
        $this->setCompletedState($table);

        //Register base fields without options for cached operations
        $this->base_fields[$table] = $this->removeOptions($this->fields[$table]);

        //Fire post updated on fields as queries, loading options etc...
        $fields = $this->firePostUpdate($model, $table);

        return $this->fields[$table];
    }

    /**
     * Remove options from base fields.
     *
     * @param  array  $fields
     * @return array
     */
    private function removeOptions(array $fields)
    {
        foreach ($fields as $key => $field) {
            if (array_key_exists('options', $field)) {
                $fields[$key]['options'] = [];
            }
        }

        return $fields;
    }

    /**
     * Set completed status of loaded fields.
     *
     * @param  string  $table
     * @return void
     */
    private function setCompletedState(string $table)
    {
        if (! in_array($table, $this->loaded_fields)) {
            $this->loaded_fields[] = $table;
        }
    }

    /**
     * Set uncompleted sate of loaded fields.
     *
     * @param  string  $table
     * @return void
     */
    private function setUncompletedState(string $table)
    {
        if (in_array($table, $this->loaded_fields)) {
            unset($this->loaded_fields[array_search($table, $this->loaded_fields)]);
        }
    }

    /**
     * Return if is completed state of rendering admin model fields.
     *
     * @param  string  $table
     * @return bool
     */
    private function isCompletedState(string $table)
    {
        return in_array($table, $this->loaded_fields);
    }

    /**
     * Fire post update events for additional mutations of relationships, options, etc...
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @param  string  $table
     * @return array
     */
    private function firePostUpdate(AdminModel $model, string $table)
    {
        $fields = $this->fields[$table];

        if (
            ! isset($this->post_update[$table])
            || count($updates = $this->post_update[$table]) == 0
        ) {
            return $fields;
        }

        foreach ($updates as $mutation) {
            $key = $mutation->getKey();

            //Skip removed columns
            if (! array_key_exists($key, $fields)) {
                continue;
            }

            $field = $mutation->getPostUpdate()($fields, $fields[$key], $key, $model);

            $fields[$key] = $field;
        }

        //Overide fields back to previous state before post update
        $this->fields[$table] = $fields;

        return $fields;
    }

    /**
     * Register dynamical mutations of fields by developer in actual model.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @param  Admin\Core\Eloquent\AdminModel|null  $param
     */
    private function addFieldsMutationIntoModel(AdminModel $model, $param)
    {
        $builder = new FieldsMutationBuilder;

        if (method_exists($model, 'mutateFields')) {
            $model->mutateFields($builder, $param);
        }

        return $builder;
    }

    /**
     * Modify group by id.
     *
     * @param  Admin\Core\Fields\Group  $group
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutationBuilder
     * @return Admin\Core\Fields\Group
     */
    private function mutateGroup($group, $mutationBuilder)
    {
        if (
            ! $group->id
            || count($mutationBuilder->groups) == 0
            || ! array_key_exists($group->id, $mutationBuilder->groups)
        ) {
            return $group;
        }

        $mutationBuilder->groups[$group->id]($group);

        return $group;
    }

    /**
     * Insert field/group on position.
     *
     * @param  string  $where
     * @param  string  $key
     * @param  array  $fields
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutationBuilder
     * @return array
     */
    private function insertInto(string $where, string $key, array $fields, $mutationBuilder)
    {
        foreach ($mutationBuilder->{$where} as $positionKey => $add_before) {
            if ($key === $positionKey) {
                foreach ($add_before as $addKey => $add_field) {
                    $fields = $this->pushFieldOrGroup($fields, $addKey, $add_field, $mutationBuilder);
                }
            }
        }

        return $fields;
    }

    /**
     * Add field, or modified group into fields list.
     *
     * @param  array  $fields
     * @param  string  $key
     * @param  array|Admin\Core\Fields\Group  $field
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutationBuilder
     * @return array
     */
    private function pushFieldOrGroup($fields, string $key, $field, $mutationBuilder)
    {
        if ($this->isFieldGroup($field)) {
            //If group is removed
            if ($field->id && in_array($field->id, $mutationBuilder->remove, true)) {
                return $fields;
            }

            $group = $this->mutateGroup($field, $mutationBuilder);

            if (is_numeric($key)) {
                $fields[] = $group;
            } else {
                $fields[$key] = $group;
            }
        } else {
            $fields[$key] = $field;
        }

        return $fields;
    }

    /**
     * Add before/after new field or remove fields for overriden admin model.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @param  array  $items
     * @param  Admin\Core\Fields\Group|null  $parentGroup
     * @return array
     */
    private function mutateGroupFields(AdminModel $model, array $items, $parentGroup = null)
    {
        $fields = [];

        $mutationBuilder = $this->mutationBuilder[$model->getTable()];

        //Push new fields, groups... or replace existing fields. Into first level of fields
        if (! $parentGroup) {
            $fields = $this->pushFields($fields, $mutationBuilder, 'push_before');
        }

        foreach ($items as $key => $field) {
            //Add before field
            $fields = $this->insertInto('before', $key, $fields, $mutationBuilder);

            //Add if is not removed
            if (! in_array($key, $mutationBuilder->remove, true)) {
                $fields = $this->pushFieldOrGroup($fields, $key, $field, $mutationBuilder);
            }

            //Add after field
            $fields = $this->insertInto('after', $key, $fields, $mutationBuilder);
        }

        //Push new fields, groups... or replace existing fields. Into first level of fields
        if (! $parentGroup) {
            $fields = $this->pushFields($fields, $mutationBuilder);
        }

        return $fields;
    }

    /**
     * Push fields from mutation builder.
     *
     * @param  array  $fields
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutationBuilder
     * @param  string  $type
     * @return array
     */
    private function pushFields(array $fields, $mutationBuilder, $type = 'push')
    {
        foreach ($mutationBuilder->{$type} as $key => $field) {
            $fields = $this->pushFieldOrGroup($fields, $key, $field, $mutationBuilder);
        }

        return $fields;
    }

    /**
     * Register fields from all groups and infinite level of sub groups or tabs.
     * Also rewrite mutated fields into groups.
     *
     * @param  AdminModel  $model
     * @param  string  $key
     * @param  array|Admin\Core\Fields\Group  $field
     * @param  Admin\Core\Fields\Group|null  $parentGroup
     * @return Group|array
     */
    private function manageGroupFields(AdminModel $model, string $key, $field, $parentGroup = null)
    {
        //If is group
        if ($group = $this->isFieldGroup($field)) {
            //If group name is not set
            if (! $group->name && ! is_numeric($key)) {
                $group->name = $key;
            }

            $fields = [];

            //Actual group will inherit parent groups add-ons
            if ($parentGroup && count($parentGroup->add) > 0) {
                $group->add = array_merge($group->add, $parentGroup->add);
            }

            //Add/remove fields/groups
            $mutated_groups = $this->mutateGroupFields($model, $group->fields, $parentGroup);

            //Register sub groups or sub fields
            foreach ($mutated_groups as $field_key => $field_from_group) {
                $mutation_previous = isset($mutation_previous) ? $mutation_previous : $this->fields[$model->getTable()];

                $mutation = $this->manageGroupFields($model, $field_key, $field_from_group, $group);

                //If is group in fields list
                if ($mutation instanceof Group) {
                    $fields[] = $mutation;

                    $mutation_previous = $this->fields[$model->getTable()];
                }

                //Add new fields into group from fields mutations
                else {
                    foreach (array_diff_key($mutation, $mutation_previous) as $key => $field) {
                        $fields[] = $key;
                    }

                    $mutation_previous = $mutation;
                }
            }

            $group->fields = $fields;

            //Register group into buffer
            if (! $parentGroup) {
                $this->registerGroup($group, $model);
            }

            return $group;
        } else {
            if ($parentGroup && count($parentGroup->add) > 0) {
                $field = $this->pushParams($field, $parentGroup->add);
            }

            //Create mutation on field
            return $this->registerField($field, str_slug($key, '_'), $model);
        }
    }

    /**
     * Return registered groups for given model.
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @return array
     */
    public function getFieldsGroups(AdminModel $model)
    {
        $table = $model->getTable();

        if (! array_key_exists($table, $this->groups)) {
            return false;
        }

        return $this->groups[$table];
    }

    /**
     * Register group into field buffer for groups.
     *
     * @param  Admin\Core\Fields\Group  $group
     * @param  AdminModel  $model
     * @return void
     */
    protected function registerGroup(Group $group, AdminModel $model)
    {
        //Update and register field
        $this->groups[$model->getTable()][] = $group;
    }

    /**
     * Register field into fields buffer.
     *
     * @param  string|array  $field
     * @param  string  $key
     * @param  AdminModel  $model
     * @param  array  $skip
     * @return array
     */
    protected function registerField($field, string $key, AdminModel $model, $skip = [])
    {
        $table = $model->getTable();

        //If no field is present, if is null or empty array value
        if ( ! $field )
            return $this->fields[$table];

        //Field mutations
        foreach ($this->mutations as $namespace) {
            //Skip namespaces
            if (in_array($namespace, $skip)) {
                continue;
            }

            if ($response = $this->mutate($namespace, $field, $key, $model)) {
                $field = $response;
            }

            //Update and register field
            $this->fields[$table][$key] = $field;
        }

        //Mutate field from mutation builder
        $this->mutateField($field, $key, $table);

        //If field need to be removed
        if (in_array($key, (array) $this->remove[$table])) {
            unset($this->fields[$table][$key]);
        }

        return $this->fields[$table];
    }

    /**
     * Convert field into stdClass and call mutation callback.
     *
     * @param  array  $field
     * @param  string  $key
     * @param  string  $table
     * @return void
     */
    private function mutateField($field, string $key, string $table)
    {
        //Mutate field by mutation builder
        if (! array_key_exists($key, $this->mutationBuilder[$table]->fields)) {
            return;
        }

        $field = new \StdClass();

        //Clone field into stdt array
        foreach ($this->fields[$table][$key] as $k => $value) {
            $field->{$k} = $value;
        }

        $this->mutationBuilder[$table]->fields[$key]($field);

        $this->fields[$table][$key] = (array) $field;
    }

    /**
     * Mutate giben field with all registred mutation rules.
     *
     * @param  string  $namespace
     * @param  string|array  $field
     * @param  string  $key
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @return array
     */
    public function mutate(string $namespace, $field, string $key = null, AdminModel $model = null)
    {
        $mutation = new $namespace;

        if ($mutation instanceof MutationRule) {
            $mutation->setFields($this->fields[$model->getTable()]);
            $mutation->setField($field);
            $mutation->setKey($key);
        }

        $this->updateFields($mutation, $field, $key, $model);

        //Creating field
        $this->createFields($mutation, $field, $key, $model);

        //Removing field
        $this->removeFields($mutation, $field, $key, $model);

        //Register attributes from mutation
        $this->registerProperties($mutation);

        //Register post updates mutators
        $this->registerPostUpdate($mutation, $field, $model);

        return $field;
    }

    /**
     * Register post updates from mutation rules.
     *
     * @param  object|Admin\Core\Fields\FieldsMutationBuilder  $mutation
     * @param  array  $field
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @return void
     */
    protected function registerPostUpdate($mutation, $field, $model)
    {
        if (
            ! method_exists($mutation, 'getPostUpdate')
            || ! $mutation->getPostUpdate()
        ) {
            return;
        }

        $this->post_update[$model->getTable()][] = $mutation;
    }

    /**
     * Register attributes from mutation builder.
     *
     * @param  class|Admin\Core\Fields\FieldsMutationBuilder  $mutation
     * @return void
     */
    protected function registerProperties($mutation)
    {
        if (property_exists($mutation, 'attributes')) {
            $this->addAttribute($mutation->attributes);
        }
    }

    /**
     * Update given field.
     *
     * @param  class|Admin\Core\Fields\FieldsMutationBuilder  $mutation
     * @param  array|string  &$field
     * @param  string|null  $key
     * @param  Admin\Core\Eloquent\AdminModel|null  $model
     * @return void
     */
    protected function updateFields($mutation, &$field, $key, $model)
    {
        //Updating field
        if (! method_exists($mutation, 'update')) {
            return;
        }

        if (
            ($response = $mutation->update($field, $key, $model))
            && is_array($response)
        ) {
            $field = $response;
        }
    }

    /**
     * Register new fields from mutation.
     *
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutation
     * @param  array|string  $field
     * @param  string|null  $key
     * @param  Admin\Core\Eloquent\AdminModel|null  $model
     * @return void
     */
    protected function createFields($mutation, $field, $key, $model)
    {
        if (method_exists($mutation, 'create')) {
            $response = $mutation->create($field, $key, $model);

            if (is_array($response)) {
                foreach ((array) $response as $key => $field) {
                    //Register field with all mutations, actual mutation will be skipped
                    $this->registerField($field, $key, $model, [get_class($mutation)]);
                }
            }
        }
    }

    /**
     * Remove fields from mutation.
     *
     * @param  Admin\Core\Fields\FieldsMutationBuilder  $mutation
     * @param  array|string  $field
     * @param  string|null  $key
     * @param  Admin\Core\Eloquent\AdminModel|null  $model
     * @return void
     */
    protected function removeFields($mutation, $field, $key, $model)
    {
        if (method_exists($mutation, 'remove')) {
            $response = $mutation->remove($field, $key, $model);

            //Get model table name
            $table = $model->getTable();

            //Remove acutal key
            if ($response === true) {
                $this->remove[$table][] = $key;
            } elseif (is_string($response)) {
                $this->remove[$table][] = $response;
            } elseif (is_array($response)) {
                foreach ($response as $key) {
                    $this->remove[$table][] = $key;
                }
            }
        }
    }
}
