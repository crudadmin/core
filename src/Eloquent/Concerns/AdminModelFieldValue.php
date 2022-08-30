<?php

namespace Admin\Core\Eloquent\Concerns;

class AdminModelFieldValue
{
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
