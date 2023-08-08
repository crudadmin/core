<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;
use Str;
use Cache;

trait RelationsMapBuilder
{
    private static $bootingRelations = [];

    /*
     * Relations cache turned off for now
     */
    protected function hasRelationsCache()
    {
        return false;
    }

    protected function bootRelationships()
    {
        $tree = $this->getCachedRelationsTree();

        foreach ($tree as $key => $callback) {
            $this->resolveRelationUsing($key, function($model) use ($callback) {
                //Get cached array version, or unpack closure
                $run = is_array($callback)
                        ? $callback
                        : $callback($model);

                //Parse methods
                foreach ($run as $method => $params) {
                    foreach ($params as $k => $param) {
                        //Boot model class
                        if ( is_string($param) && $param[0] == '$' ){
                            $params[$k] = AdminCore::getModelByTable(substr($param, 1));
                        }
                    }

                    $model = $model->{$method}(...$params);
                }

                return $model;
            });
        }
    }

    public function getCachedRelationsTree()
    {
        if ( $this->hasRelationsCache() ) {
            $cacheKey = 'relations.'.$this->getFieldsCacheModelKey();

            //TODO: split cache into one single TREE, not each model to have own cache file.
            //This may slow down cache process. But cache is turned off for now.
            return Cache::rememberForever($cacheKey, function(){
                return $this->getRelationsTree();
            });
        }

        //APP Runtime relations
        else {
            return $this->getRelationsTree();
        }
    }

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
            $modelTree = $this->serializeRelationsForCache($modelTree);

            $tree = array_merge(
                $tree,
                $this->prepareCasesVariants($modelTree)
            );

            $tree = array_merge(
                $tree,
                $this->prepareUpperLowerCasesVariants($tree)
            );
        }

        ksort($tree);

        static::$bootingRelations[static::class] = false;

        return $tree;
    }

    private function serializeRelationsForCache($tree)
    {
        //Serialize relationship from closure into array
        if ( $this->hasRelationsCache() ) {
            foreach ($tree as $key => $callback) {
                $tree[$key] = $callback($this);
            }
        }

        return $tree;
    }

    /**
     * Create all variants of forms
     * eg: ->parentRelation, ->parent_relation
     *
     * @param  array  $tree
     *
     * @return  array
     */
    private function prepareCasesVariants($tree)
    {
        $variants = [];

        foreach ($tree as $key => $relation) {
            //Original forms
            $variants[$key] = $relation;

            //Snake: payment_method
            $variants[Str::snake($key)] = $relation;

            //Studly case: PaymentMethod
            $variants[Str::studly($key)] = $relation;
        }

        return $variants;
    }

    /**
     * Create all variants of uppercase/lowercase forms
     *
     * @param  array  $tree
     *
     * @return  array
     */
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
                            return [
                                'hasOne' => [
                                    $relationModel::class,
                                    $model->getForeignColumn($relationModel->getTable())
                                ]
                            ];
                        }

                        //Support for recursive BelongsToModel in oposite direction
                        //When child is calling parent in singular mode.
                        if ( $model::class == $relationModel::class ){
                            return [
                                'belongsTo' => [
                                    $relationModel::class,
                                    $model->getForeignColumn($relationModel->getTable())
                                ]
                            ];
                        }

                        return [
                            'hasMany' => [
                                $relationModel::class,
                                $relationModel->getForeignColumn($model->getTable())
                            ]
                        ];
                    },
                    function($model) use ($relationModel) {
                        return [
                            'hasMany' => [
                                $relationModel::class,
                                $relationModel->getForeignColumn($model->getTable()),
                            ]
                        ];
                    }
                );

                $tree = array_merge($tree, $forms);
            }

            // Reverse call for belongsToModel relation. Call parent from child.
            if ( in_array($relationBaseName, $currentBelongsToModel) ) {
                $forms = $relationModel->getRelationForms(
                    $this,
                    function($model) use ($relationModel) {
                        return [
                            'belongsTo' => [
                                $relationModel::class,
                                $model->getForeignColumn($relationModel->getTable())
                            ]
                        ];
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
                    return [
                        'belongsTo' => [
                            '$'.$properties[0], //Table relation class
                            $properties[4]
                        ]
                    ];
                };

                $relationName = Str::replaceLast('_id', '', $fieldKey);

                $tree[$relationName] = $relation;
            }
        }


        //Reverse belongsTo field relation
        foreach (AdminCore::getAdminModels() as $relationModel) {
            foreach ($relationModel->getFields() as $fieldKey => $field) {
                if ( isset($field['belongsTo']) ) {
                    $properties = $relationModel->getRelationProperty($fieldKey, 'belongsTo');

                    if ( $properties[0] == $this->getTable() ) {
                        $relation = function($model) use ($relationModel, $fieldKey) {
                            return [
                                'hasMany' => [
                                    $relationModel::class,
                                    $fieldKey,
                                    $relationModel->getKeyName(),
                                ]
                            ];
                        };

                        $pluralBasename = Str::plural(class_basename($relationModel));

                        //Full model name, eg: ->productsGallery
                        $tree[$pluralBasename] = $relation;

                        //Final model name, eg: ->gallery
                        $tree[implode('_', array_slice(explode('_', Str::snake($pluralBasename)), -1))] = $relation;
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
                    return [
                        'belongsToMany' => [
                            $fieldRelationModel::class,
                            $properties[3],
                            $properties[6],
                            $properties[7]
                        ],
                        'orderBy' => [
                            $properties[3].'.id', 'asc'
                        ],
                    ];
                };

                $tree[$fieldKey] = $relation;
            }
        }


        //Reverse belongsToMany field relation
        foreach (AdminCore::getAdminModels() as $relationModel) {
            foreach ($relationModel->getFields() as $fieldKey => $field) {
                if ( isset($field['belongsToMany']) ) {
                    $properties = $relationModel->getRelationProperty($fieldKey, 'belongsToMany');

                    if ( $properties[0] == $this->getTable() ) {
                        $relation = function($model) use ($relationModel, $properties) {
                            return [
                                'belongsToMany' => [
                                    $relationModel::class,
                                    $properties[3],
                                    $properties[7],
                                    $properties[6],
                                ]
                            ];
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
            Str::plural($basename) => $relationPlural,
            Str::singular($basename) => $relationSingular,
            array_slice(explode('_', Str::snake($basename)), -1)[0] => $relationSingular,
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
