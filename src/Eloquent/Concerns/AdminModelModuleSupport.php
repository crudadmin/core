<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Core\Eloquent\AdminModel;

interface AdminModelModuleSupport
{
    /**
     * Check if admin module is active
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     * @return bool
     */
    public function isActive($model);

    /**
     * Mutate fields properties
     *
     * @param  Admin\Core\Fields\FieldsMutationBuilder $fields
     */
    // public function mutateFields($fields) {}

    /**
     * Mutate AdminModel properties
     * setOptionsProperty
     * setActiveProperty
     * ...
     *
     * @param  mixed
     */
    // public function set{PropertyName}Property($value) {}
}
