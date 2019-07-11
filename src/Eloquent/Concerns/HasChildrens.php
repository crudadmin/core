<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;

trait HasChildrens
{
    /**
     * Automatically and easy assign children relation into model.
     *
     * @param  string  $method
     * @param  bool  $get
     * @return Illuminate\Database\Eloquent\Relations\Relation|bool
     */
    protected function checkForChildrenModels(string $method, $get = false)
    {
        $basename_class = class_basename(get_class($this));

        $method_singular = strtolower(str_singular($method));

        //Child model name
        $child_model_name = strtolower(str_plural($basename_class) . $method_singular);

        //Check if exists child with model name
        $relation = AdminCore::hasAdminModel($child_model_name)
                        ? $this->returnAdminRelationship($child_model_name, $get)
                        : null;

        //If is found relation, or if is called relation in singular mode, that means, we don't need hasMany, bud belongsTo relation
        if ($relation || $method == $method_singular) {
            return $relation;
        }

        //Check by last model convention name
        foreach (AdminCore::getAdminModelNamespaces() as $migration_date => $modelname) {
            $basename = class_basename($modelname);

            //Check if model ends with needed relation name
            if (last(explode('_', snake_case($basename))) == $method_singular) {
                if (($response = $this->returnAdminRelationship(str_plural($basename), $get, [
                    $migration_date => $modelname,
                ])) === false) {
                    continue;
                }

                return $response;
            }
        }

        return false;
    }
}
