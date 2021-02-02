<?php

namespace Admin\Core\Fields\Validation;

interface ValidationMutatorInterface
{
    public function mutateField(string $key, array $attributes, string $originalKey);
}