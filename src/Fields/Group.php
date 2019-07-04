<?php

namespace Admin\Core\Fields;

class Group
{
    /*
     * Id of group
     */
    public $id = null;

    /*
     * Group name
     */
    public $name = null;

    /*
     * Fields list
     */
    public $fields = [];

    /*
     * What parameters should be added
     */
    public $add = [];

    /*
     * Group type
     */
    public $type = 'default';

    /*
     * Boot group and add fields into class
     */
    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    /*
     * Set type of group
     * group/tab
     */
    public function type($type = 'group')
    {
        $this->type = $type;

        return $this;
    }
}
?>