<?php

namespace Admin\Core\Eloquent\Concerns;

trait HasPublishable
{
    /**
     * Fetch also temporary published row
     *
     * @var  bool
     */
    private static $withTemporaryPublished = false;

    public function withTemporaryPublished($state = true)
    {
        self::$withTemporaryPublished = $state;

        return $this;
    }

    /**
     * Returns also unpublished rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeWithUnpublished($query)
    {
        $query->withoutGlobalScope('publishable');
    }

    /**
     * Returns only published rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeWithPublished($query)
    {
        $query->where(function($query){
            $query->where($this->getTable().'.published_at', '!=', null)
                  ->whereRAW($this->getTable().'.published_at <= NOW()');

            if ( $this->publishableState == true && $this->hasTemporaryPublished() ) {
                $query->orWhereNotNull('published_state->av');
            }
        });
    }


    private function hasTemporaryPublished()
    {
        if ( admin() ) {
            return true;
        }

        return self::$withTemporaryPublished == true;
    }

    public function isAdminPublished()
    {
        if ( $this->published_at ){
            return true;
        }

        return admin() && (($this->published_state ?: [])['av'] ?? 0) == 1;
    }
}