<?php

namespace Admin\Core\Fields\Validation;

use Admin\Core\Fields\Validation\ValidationMutator;
use Validator;

class FileMutator extends ValidationMutator implements ValidationMutatorInterface
{
    public static $requiredAttributes = [
        'required', 'required_if', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all'
    ];

    public function mutateField(string $key, array $attributes, string $originalKey)
    {
        return $this->removeRequiredFromUploadedFields(
            $attributes, $key, $originalKey
        );
    }

    public function whitelistKeys($key)
    {
        //We need allow pass this keys into whole request
        if ( $this->getModel()->isFieldType($key, 'file') === true ){
            return ['$remove_'.$key, '$uploaded_'.$key];
        }
    }

    /**
     * Check if given field is required in request
     *
     * @param  string  $key
     * @return  bool
     */
    private function isKeyRequired($key, $attributes)
    {
        $model = $this->getModel();
        $request = $this->getRequest();

        //We want check, if this field with empty value will pass validation
        //when some of required rules are applied. If it won't, we can consider this field as required.
        foreach (FileMutator::$requiredAttributes as $attribute) {
            $attributeLength = strlen($attribute);

            foreach ($attributes as $rule) {
                if ( $rule === $attribute || substr($rule, 0, $attributeLength + 1) === $attribute.':' ) {
                    $emptyFieldRequestData = [
                        $key => null,
                    ] + $request->all();

                    $validator = Validator::make($emptyFieldRequestData, [
                        $key => $rule
                    ]);

                    if ( $validator->fails() ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /*
     * If field has required rule, but file is already uploaded in the server, then
     * remove required rule, because file is not now required
     */
    private function removeRequiredFromUploadedFields($data, $key, $originalKey)
    {
        $model = $row = $this->getModel();
        $request = $this->getRequest();

        if (
            $model->isFieldType($originalKey, 'file')
            && $this->isKeyRequired($originalKey, $data)
            && ! empty($row->{$originalKey})
            && ! $request->has('$remove_'.$key)
        ) {
            $isEmptyFiles = ! $model->hasFieldParam($originalKey, 'multiple', true)
                            || (
                                $request->has('$uploaded_'.$originalKey)
                                && count((array) $request->get('$uploaded_'.$originalKey)) > 0
                            );

            if ($isEmptyFiles) {
                if ( ($k = array_search('required', $data)) !== false ) {
                    unset($data[$k]);
                }

                //We want remove all additional required rules from this multiple field
                foreach (self::$requiredAttributes as $rule) {
                    foreach ($data as $fk => $fieldRule) {
                        if ( starts_with($fieldRule, $rule.':') ) {
                            unset($data[$fk]);
                        }
                    }
                }
            }
        } else {
            $this->addRequiredRuleForDeletedFiles($data, $key, $originalKey);
        }

        return $data;
    }

    /*
     * If file has been deleted from server and is required, then add back required rule for this file.
     */
    private function addRequiredRuleForDeletedFiles(&$data, $key, $originalKey)
    {
        $model = $this->getModel();
        $request = $this->getRequest();

        //If field is required and has been removed, then remove nullable rule for a file requirement
        if ($request->has('$remove_'.$key) && ! $model->hasFieldParam($originalKey, 'multiple', true)) {
            $request->merge([$key => null]);

            if (
                self::canRemoveNullable($model, $originalKey, $key)
                && $model->hasFieldParam($originalKey, 'required', true)
                && ($k = array_search('nullable', $data)) !== false
             ) {
                unset($data[$k]);

                $data[] = 'required';
            }
        }

        //Add required value for empty multi upload fields
        if (
            ! $request->has('$uploaded_'.$key)
            && $model->hasFieldParam($originalKey, 'multiple', true)
            && self::canRemoveNullable($model, $originalKey, $key)
            && $model->hasFieldParam($originalKey, 'required', true)
            && ($k = array_search('nullable', $data)) !== false
        ) {
            unset($data[$k]);

            $data[] = 'required';
        }
    }

}