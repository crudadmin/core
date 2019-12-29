<?php

namespace Admin\Core\Eloquent;

interface AdminModelModule
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
