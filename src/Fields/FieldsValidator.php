<?php

namespace Admin\Core\Fields;

use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Eloquent\AdminModel;
use Admin\Exceptions\ValidationException;
use Illuminate\Validation\ValidationException as BaseValidationExpetion;
use Illuminate\Http\Request;
use Validator;

class FieldsValidator
{
    /**
     * Only this fields will be validated from given AdminModel
     *
     * @var  array
     */
    protected $useOnly = [];

    /**
     * Merge given fields into rules
     *
     * @var  array
     */
    protected $merge = [];

    /**
     * HTTP request
     *
     * @var  Illuminate\Http\Request
     */
    protected $request;

    /**
     * AdminModel
     *
     * @var  Admin\Core\Eloquent\AdminModel
     */
    protected $model;

    /**
     * Constructor
     *
     * @param  AdminModel  $model
     * @param  Request|null  $request
     */
    public function __construct(AdminModel $model, Request $request = null)
    {
        $this->model = $model;

        $this->request = $request ?: request();
    }

    /**
     * Returns AdminModel
     *
     * @return  Admin\Core\Eloquent\AdminModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Validate only given fields
     * ->only(['firstname', 'lastname', 'password'])
     * ->only(MyRequest::class)
     *
     * @param  array|string  $onlyFields
     * @return  this
     */
    public function only($onlyFields)
    {
        //Allow only given fields from array set
        if ( is_array($onlyFields) ) {
            $this->useOnly = array_merge($this->useOnly, $onlyFields);
        }


        //If Request has been received
        else if ( is_string($onlyFields) ) {
            //Receive rules from given Request
            $requestRules = (new $onlyFields)->rules();

            //Allow only data from given request
            $this->only(array_keys($requestRules));

            //Push and merge given fields with admin fields
            $this->merge($requestRules);
        }

        return $this;
    }

    /**
     * Merge given fields into actual fields
     * ->merge(['firstname' => 'required|max:10', 'lastname' => 'required'])
     * ->merge(MyRequest::class)
     *
     * @param  array  $mergeFields
     * @return  this
     */
    public function merge($mergeFields)
    {
        $this->merge = $this->mergeRules($this->merge, $mergeFields);

        return $this;
    }

    /**
     * If array is given, return array
     * If laravel Request with custom rules is given, return his rules as array
     *
     * @return  array
     */
    private function mergeRules($rules, $newRules) : array
    {
        //Receive rules from given Request
        if ( is_string($newRules) ) {
            $newRules = (new $newRules)->rules();
        }

        foreach ($newRules as $key => $fieldRules) {
            $toMerge = (new FieldToArray)->update($fieldRules);

            $rules[$key] = array_unique(array_filter(array_merge(
                @$rules[$key] ?: [],
                $this->getModel()->fieldToString($toMerge)
            )));
        }

        return $rules;
    }

    /**
     * Returns error response after wrong validation.
     *
     * @param  Illuminate\Validation\Validator  $validator
     *
     * @return Illuminate\Http\Response
     */
    public function buildFailedValidationResponse($validator)
    {
        //If is ajax request
        if ($this->request->expectsJson()) {
            $error = BaseValidationExpetion::withMessages($validator->errors()->getMessages());

            throw $error;
        }

        return redirect(url()->previous())->withErrors($validator)->withInput();
    }

    /**
     * Build rules validator
     *
     * @return  void
     */
    public function getRules()
    {
        $model = $this->getModel();

        $rules = $model->getValidationRules($model);

        //Apply only given fields
        if ( count($this->useOnly) > 0 ) {
            $rules = array_intersect_key($rules, array_flip(array_values($this->useOnly)));
        }

        //Merge given fields into request
        $rules = $this->mergeRules($rules, $this->merge);

        return $rules;
    }

    /**
     * Validate incoming request
     *
     * @return  this
     */
    public function validate()
    {
        $validator = Validator::make($this->request->all(), $this->getRules());

        if ($validator->fails()) {
            throw new ValidationException(
                $this->buildFailedValidationResponse($validator)
            );
        }

        return $this;
    }

    /**
     * Returns request data mutated with admin mutators
     *
     * @return  array
     */
    public function getData()
    {
        $rules = $this->getRules();

        $keys = array_keys($rules);

        return $this->getModel()->muttatorsResponse(
            $this->request->only($keys), //we need pass only allowed data set
            $keys,
            $rules
        );
    }
}
