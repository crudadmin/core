<?php

namespace Admin\Core\Model;

use Illuminate\Database\Eloquent\Model;

class AdminModel extends Model
{
    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = null;

    /*
     * Enable adding new rows
     */
    protected $insertable = true;

    /*
     * Enable updating rows
     */
    protected $editable = true;

    /*
     * Enable deleting rows
     */
    protected $deletable = true;

    /*
     * Enable publishing rows
     */
    protected $publishable = true;

    /*
     * Enable sorting rows
     */
    protected $sortable = true;

    /*
     * Automatic sluggable
     */
    protected $sluggable = null;

    /*
     * Skipping dropping columns into database in migration
     */
    protected $skipDropping = [];

    /*
     * Automatic form and database generation
     */
    protected $fields = [];

    /*
     * Returns also unpublished rows
     */
    public function scopeWithUnpublished($query)
    {
        $query->withoutGlobalScope('publishable');
    }

    public function __construct(array $attributes = [])
    {
        //Boot base model trait
        $this->initTrait();

        parent::__construct($attributes);
    }

}