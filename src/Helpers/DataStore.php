<?php

namespace Admin\Core\Helpers;

class DataStore
{
    /*
     * Store data
     */
    private static $store = [];

    /**
     * Returns store data.
     * @param  string|null $key
     * @return array
     */
    public static function getStore($key = null)
    {
        if ($key) {
            return isset(self::$store[$key]) ? self::$store[$key] : null;
        }

        return self::$store;
    }

    /**
     * Get property from store.
     * @param  string $key
     * @param  mixed $default
     * @param  string $store
     * @return mixed
     */
    public static function get($key, $default = null, $store = 'global')
    {
        if (self::has($key, $store)) {
            return self::$store[$store][$key];
        } else {
            return $default;
        }
    }

    /**
     * Save property with value into store.
     * @param string $key
     * @param mixed $data
     * @param mixed $store
     * @return mixed
     */
    public static function set($key, $data, $store = 'global')
    {
        return self::$store[$store][$key] = $data;
    }

    /**
     * Checks if property exists in buffer.
     * @param  string  $key
     * @param  string  $store
     * @return bool
     */
    public static function has($key, $store = 'global')
    {
        return isset(self::$store[$store]) && array_key_exists($key, self::$store[$store]);
    }

    /**
     * Push data into array store.
     * @param  string $key
     * @param  mixed $value
     * @param  mixed $arrayKey
     * @param  string $store
     * @return mixed
     */
    public static function push($key, $data, $arrayKey = null, $store = 'global')
    {
        if (! isset(self::$store[$store]) || ! array_key_exists($key, self::$store[$store]) || ! is_array(self::$store[$store][$key])) {
            self::$store[$store][$key] = [];
        }

        //Save unassociative value
        if ($arrayKey === null) {
            return self::$store[$store][$key][] = $data;
        }

        //Save value with key
        return self::$store[$store][$key][$arrayKey] = $data;
    }

    /**
     * Save data into instance. On second access data will be retrieved from storage.
     * @param  string  $key
     * @param  mixed  $data
     * @param  mixed  $store
     * @return mixed
     */
    public static function cache($key, $data, $store = 'global')
    {
        if (self::has($key, $store)) {
            return self::get($key, null, $store);
        }

        //If is passed data callable function
        if (is_callable($data)) {
            $data = call_user_func($data);
        }

        return self::set($key, $data, $store);
    }
}
