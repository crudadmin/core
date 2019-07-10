<?php

namespace Admin\Core\Eloquent\Concerns;

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
    private function assignArrayByPath(&$arr, string $path, $value, $separator='.') {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $row = [];

        if ( is_array($value) )
        {
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
     * @param  string  $separator
     * @param  array  &$arr
     * @return array
     */
    public function getModelSettings($separator = '.', &$arr = [])
    {
        $settings = (array)$this->getProperty('settings');

        $data = [];

        foreach ($settings as $path => $value)
        {
            $row = [];

            //Create multidimensional array
            $this->assignArrayByPath($row, $path, $value);

            $data = array_merge_recursive($data, $row);
        }

        return $data;
    }
}