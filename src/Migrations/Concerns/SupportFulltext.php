<?php

namespace Admin\Core\Migrations\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Illuminate\Support\Facades\DB;

trait SupportFulltext
{
    /**
     * Set column fulltext.
     * @param  AdminModel       $model
     * @return void
     */
    protected function setTableFullText($table, AdminModel $model)
    {
        $fullTextColumns = [];

        foreach ($model->getFields() as $key => $field) {
            if ( $model->hasFieldParam($key, 'fulltext', true) ){
                $fullTextColumns[] = $key;
            }
        }

        if ( count($fullTextColumns) == 0 ){
            return;
        }

        $indexes = $this->getModelIndexes($model);

        $indexName = $this->getIndexName($model, 'full', 'fulltext');

        if ( isset($indexes[$indexName]) ){
            //If index does exists, but does not match with actual indexes columns set
            if ( $indexes[$indexName]['columns'] != $fullTextColumns ) {
                $table->dropIndex($indexName);
            }

            //If index does exists, we can skip creating index
            else {
                return;
            }
        }

        $table->fullText($fullTextColumns, $indexName);
    }
}