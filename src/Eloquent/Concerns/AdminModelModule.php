<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Core\Eloquent\AdminModel;

class AdminModelModule
{
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
