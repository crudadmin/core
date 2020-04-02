<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Core\Eloquent\AdminModel;

class AdminModelModule
{
    /**
     * Boot admin module on model init state
     *
     * @return  void
     */
    // public function boot();
    // {
    //     //..
    // }

    /**
     * Set AdminModel
     *
     * @param  Admin\Core\Eloquent\AdminModel  $model
     */
    public function setModel(AdminModel $model)
    {
        $this->model = $model;
    }

    /**
     * Get AdminModel
     *
     * @return  Admin\Core\Eloquent\AdminModel
     */
    public function getModel()
    {
        return $this->model;
    }
}
