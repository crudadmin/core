<?php

namespace Admin\Core\Fields\Concerns;

use Admin\Core\Fields\Mutations;

trait HasMutations
{
    /**
     * This mutations will be applied into field in admin model.
     *
     * @var array
     */
    protected $mutations = [
        Mutations\FieldToArray::class,
        Mutations\AddGlobalRules::class,
        Mutations\AddAttributeRules::class,
        Mutations\BelongsToAttributeMutator::class,
    ];

    /**
     * Get mutations list.
     *
     * @return array
     */
    public function getMutations()
    {
        return $this->mutations;
    }

    /**
     * Add new mutation into list.
     *
     * @param  array|string  $namespace
     * @return void
     */
    public function addMutation($namespace)
    {
        foreach (array_wrap($namespace) as $namespace) {
            $this->mutations[] = $namespace;
        }
    }
}
