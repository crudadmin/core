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

    /*
     * Mutate admin fields
     */
    // public function mutateFields() { }
}
