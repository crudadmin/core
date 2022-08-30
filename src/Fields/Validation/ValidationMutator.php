<?php

namespace Admin\Core\Fields\Validation;

use Admin\Core\Eloquent\AdminModel;
use Localization;

class ValidationMutator
{
    protected $model;
    protected $request;

    public function __construct(AdminModel $model, $request)
    {
        $this->model = $model;
        $this->request = $request;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /*
     * Check if file does not have locales, or if has, then check if is default language
     */
    public static function canRemoveNullable($model, $originalKey, $key)
    {
        return ! $model->hasFieldParam($originalKey, 'locale', true)
               || last(explode('.', str_replace('.*', '', $key))) == Localization::getDefaultLanguage()->slug;
    }
}