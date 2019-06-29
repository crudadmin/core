<?php

namespace Admin\Core\Helpers;

class DataStore
{
    /*
     * Store data
     */
    private $store = [];

    /**
     * Returns store data
     * @return array
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Get property from store
     * @param  string $key
     * @param  mixed $default
     * @param  string $store
     * @return mixed
     */
    public function get($key, $default = null, $store = 'global')
    {
        if ( $this->has($key, $store) )
            return $this->store[$store][$key];
        else
            return $default;
    }

    /**
     * Save property with value into store
     * @param string $key
     * @param mixed $data
     * @param mixed $store
     * @return mixed
     */
    public function set($key, $data, $store = 'global')
    {
        return $this->store[$store][$key] = $data;
    }

    /**
     * Checks if property exists in buffer
     * @param  string  $key
     * @param  string  $store
     * @return boolean
     */
    public function has($key, $store = 'global')
    {
        return isset($this->store[$store]) && array_key_exists($key, $this->store[$store]);
    }

    /**
     * Push data into array store
     * @param  string $key
     * @param  mixed $value
     * @param  mixed $arrayKey
     * @param  string $store
     * @return mixed
     */
    public function push($key, $data, $arrayKey = null, $store = 'global')
    {
        if ( !isset($this->store[$store]) || !array_key_exists($key, $this->store[$store]) || !is_array($this->store[$store][$key]) )
            $this->store[$store][$key] = [];

        //Save unassociative value
        if ( $arrayKey === null )
            return $this->store[$store][$key][] = $data;

        //Save value with key
        return $this->store[$store][$key][$arrayKey] = $data;
    }

    /**
     * Save data into instance. On second access data will be retrieved from storage
     * @param  string  $key
     * @param  mixed  $data
     * @param  mixed  $store
     * @return mixed
     */
    public function cache($key, $data, $store = 'global')
    {
        if ( $this->has($key) )
            return $this->get($key, $store);

        //If is passed data callable function
        if ( is_callable($data) )
            $data = call_user_func($data);

        return $this->set($key, $data, $store);
    }
}