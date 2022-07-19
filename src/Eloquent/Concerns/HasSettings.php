<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Helpers\Localization\AdminResourcesSyncer;

trait HasSettings
{
    /**
     * Convert inline settings into array.
     *
     * @param  array  &$arr
     * @param  string  $path
     * @param  mixed  $value
     * @param  string  $separator
     * @return void
     */
    private function assignArrayByPath(&$arr, string $path, $value, $separator = '.')
    {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $row = [];

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                //Create multidimensional array
                $this->assignArrayByPath($row, $k, $v);
            }
        }

        $arr = is_array($value) ? $row : $value;
    }

    /**
     * Returns model settings in array.
     *
     * @return array
     */
    public function getModelSettings()
    {
        $settings = (array) $this->getProperty('settings');

        $data = [];

        foreach ($settings as $path => $value) {
            $row = [];

            //Create multidimensional array
            $this->assignArrayByPath($row, $path, $value);

            $data = array_merge_recursive($data, $row);
        }

        //Translate columns and values
        foreach (['title', 'buttons'] as $key) {
            if ( array_key_exists($key, $data) ){
                foreach ($data[$key] as $k => $value) {
                    if ( is_string($value) ){
                        $data[$key][$k] = AdminResourcesSyncer::translate($value);
                    }
                }
            }
        }

        //Translate columns
        if ( array_key_exists('columns', $data) ){
            foreach ($data['columns'] as $key => $item) {
                if ( isset($data['columns'][$key]['name']) ){
                    $data['columns'][$key]['name'] = AdminResourcesSyncer::translate($data['columns'][$key]['name']);
                }

                if ( isset($data['columns'][$key]['title']) ){
                    $data['columns'][$key]['title'] = AdminResourcesSyncer::translate($data['columns'][$key]['title']);
                }
            }
        }

        return $data;
    }
}
