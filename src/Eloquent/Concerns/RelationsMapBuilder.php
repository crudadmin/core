<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;
use Str;

trait RelationsMapBuilder
{
    private static $bootingRelations = [];

    public function getRelationsTree()
    {
        //Fix infinite loop
        if ( (static::$bootingRelations[static::class] ?? false) === true ) {
            return [];
        }

        static::$bootingRelations[static::class] = true;

        $tree = [];

        foreach ([
            $this->getChildrenModelsRelations(),
            $this->getBelongsToFieldRelations(),
            $this->getBelongsManyToFieldRelations(),
        ] as $modelTree) {
            $tree = array_merge(
                $tree,
                $this->prepareUpperLowerCasesVariants($modelTree)
            );
        }

        static::$bootingRelations[static::class] = false;

        return $tree;
    }

    protected function bootRelationships()
    {
        $tree = $this->getRelationsTree();

        foreach ($tree as $key => $callback) {
            $this->resolveRelationUsing($key, $callback);
        }
    }

    private function prepareUpperLowerCasesVariants($tree)
    {
        $variants = [];

        foreach ($tree as $key => $relation) {
            //Full lower case
            $variants[Str::lower($key)] = $relation;

            //First letter upper
            $variants[Str::ucfirst($key)] = $relation;

            //First letter lower
            $variants[Str::lcfirst($key)] = $relation;
        }

        return $variants;
    }

    //Todo if category call category belongsToModel
    private function getChildrenModelsRelations()
    {
        $tree = [];

        $classBaseName = class_basename($this);
        $currentBelongsToModel = $this->getBelongsToRelation(true);

        foreach (AdminCore::getAdminModels() as $relationModel) {
            $relationBaseName = class_basename($relationModel);
            $belongsToModel = $relationModel->getBelongsToRelation(true);

            if ( in_array($classBaseName, $belongsToModel) ) {
                $forms = $relationModel->getRelationForms(
                    $this,
                    function($model) use ($relationModel) {
                        //Returns single child support
                        if ( $relationModel->maximum == 1 ){
                            return $model->hasOne(
                                $relationModel,
                                $model->getForeignColumn($relationModel->getTable())
                            );
                        }

                        //Support for recursive BelongsToModel in oposite direction
                        //When child is calling parent in singular mode.
                        if ( $model::class == $relationModel::class ){
                            return $model->belongsTo(
                                $relationModel,
                                $model->getForeignColumn($relationModel->getTable())
                            );
                        }

                        return $model->hasMany(
                            $relationModel,
                            $relationModel->getForeignColumn($model->getTable())
                        );
                    },
                    function($model) use ($relationModel) {
                        return $model->hasMany(
                            $relationModel,
                            $relationModel->getForeignColumn($model->getTable())
                        );
                    }
                );

                $tree = array_merge($tree, $forms);
            }

            // Reverse call for belongsToModel relation. Call parent from child.
            if ( in_array($relationBaseName, $currentBelongsToModel) ) {
                $forms = $relationModel->getRelationForms(
                    $this,
                    function($model) use ($relationModel) {
                        return $model->belongsTo(
                            $relationModel,
                            $model->getForeignColumn($relationModel->getTable())
                        );
                    }
                );

                $tree = array_merge($tree, $forms);
            }
        }

        return $tree;
    }

    private function getBelongsToFieldRelations()
    {
        $tree = [];

        foreach ($this->getFields() as $fieldKey => $field) {
            if ( isset($field['belongsTo']) ){
                $properties = $this->getRelationProperty($fieldKey, 'belongsTo');


                $relation = function($model) use ($properties) {
                    $fieldRelationModel = AdminCore::getModelByTable($properties[0]);

                    return $model->belongsTo(
                        $fieldRelationModel,
                        $properties[4]
                    );
                };

                $tree[Str::replaceLast('_id', '', $fieldKey)] = $relation;
            }
        }


        //Reverse belongsTo field relation
        foreach (AdminCore::getAdminModels() as $relationModel) {
            foreach ($relationModel->getFields() as $fieldKey => $field) {
                if ( isset($field['belongsTo']) ) {
                    $properties = $relationModel->getRelationProperty($fieldKey, 'belongsTo');

                    if ( $properties[0] == $this->getTable() ) {
                        $relation = function($model) use ($relationModel, $fieldKey) {
                            return $model->hasMany(
                                $relationModel,
                                $fieldKey,
                                $relationModel->getKeyName(),
                            );
                        };

                        $pluralBasename = Str::plural(class_basename($relationModel));

                        $tree[$pluralBasename] = $relation;
                        $tree[Str::studly(implode('_', array_slice(explode('_', Str::snake($pluralBasename)), -1)))] = $relation;
                    }
                }
            }
        }

        return $tree;
    }

    private function getBelongsManyToFieldRelations()
    {
        $tree = [];

        foreach ($this->getFields() as $fieldKey => $field) {
            if ( isset($field['belongsToMany']) ){
                $properties = $this->getRelationProperty($fieldKey, 'belongsToMany');

                $fieldRelationModel = AdminCore::getModelByTable($properties[0]);

                $relation = function($model) use ($fieldRelationModel, $properties) {
                    return $model->belongsToMany(
                        $fieldRelationModel,
                        $properties[3],
                        $properties[6],
                        $properties[7]
                    )->orderBy($properties[3].'.id', 'asc');
                };

                $tree[$fieldKey] = $relation; //todo: may be removed?
                $tree[Str::studly($fieldKey)] = $relation; //todo: test
            }
        }


        //Reverse belongsToMany field relation
        foreach (AdminCore::getAdminModels() as $relationModel) {
            foreach ($relationModel->getFields() as $fieldKey => $field) {
                if ( isset($field['belongsToMany']) ) {
                    $properties = $relationModel->getRelationProperty($fieldKey, 'belongsToMany');

                    if ( $properties[0] == $this->getTable() ) {
                        $relation = function($model) use ($relationModel, $properties) {
                            return $model->belongsToMany(
                                $relationModel,
                                $properties[3],
                                $properties[7],
                                $properties[6],
                            );
                        };

                        $pluralBasename = Str::plural(class_basename($relationModel));

                        $tree[$pluralBasename] = $relation;
                        $tree[Str::studly(implode('_', array_slice(explode('_', Str::snake($pluralBasename)), -1)))] = $relation;
                    }
                }
            }
        }

        return $tree;
    }

    private function getRelationForms($parentModel, $relationSingular, $relationPlural = null)
    {
        $forms = [];

        $basename = class_basename($this);
        $basenameSnake = Str::snake(class_basename($this));

        $forms = [
            Str::singular($basename) => $relationSingular,
            Str::plural($basename) => $relationPlural,
        ];

        if ( $parentModel ) {
            $parentModelBasename = class_basename($parentModel);
            $parentModelBasenameSnake = Str::snake($parentModelBasename);

            $snakeForms = [
                Str::snake(Str::plural($parentModelBasename)).'_',
                Str::snake(Str::singular($parentModelBasename)).'_',
            ];

            //Support for recursive relationships
            if ( $this->getTable() == $parentModel->getTable() ){
                $snakeForms = array_map(function($item){
                    return implode('_', array_slice(explode('_', $item), 0, -2)).'_';
                }, $snakeForms);
            }

            foreach ($snakeForms as $name) {
                if ( Str::startsWith($basenameSnake, $name) ){
                    $snakeForm = Str::replaceFirst($name, '', $basenameSnake);

                    $forms[Str::singular($snakeForm)] = $relationSingular;
                    $forms[Str::plural($snakeForm)] = $relationPlural;
                }
            }
        }

        return array_filter($forms);
    }
}
