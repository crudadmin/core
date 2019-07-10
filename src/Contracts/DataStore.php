<?php

namespace Admin\Core\Contracts;

use DataStore as Store;

trait DataStore
{
    /**
     * Get property name where will be stored all data.
     *
     * @return string
     */
    protected function getStoreKey()
    {
        return get_class($this);
    }

    /**
     * Get store data.
     *
     * @return mixed
     */
    public function getStore()
    {
        return Store::getStore($this->getStoreKey());
    }

    /**
     * Get property from store.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Store::get($key, $default, $this->getStoreKey());
    }

    /**
     * Save property with value into store.
     *
     * @param string $key
     * @param mixed $data
     * @param mixed $store
     * @return mixed
     */
    public function set($key, $data)
    {
        return Store::set($key, $data, $this->getStoreKey());
    }

    /**
     * Checks if property exists in buffer.
     *
     * @param  string  $key
     * @param  string  $store
     * @return boolean
     */
    public function has($key)
    {
        return Store::has($key, $this->getStoreKey());
    }

    /**
     * Push data into array store.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  mixed $arrayKey
     * @param  string $store
     * @return mixed
     */
    public function push($key, $value, $arrayKey = null)
    {
        return Store::push($key, $value, $arrayKey, $this->getStoreKey());
    }

    /**
     * Save data into instance. On second access data will be retrieved from storage.
     *
     * @param  string  $key
     * @param  mixed  $data
     * @param  mixed  $store
     * @return mixed
     */
    public function cache($key, $data)
    {
        return Store::cache($key, $data, $this->getStoreKey());
    }
}