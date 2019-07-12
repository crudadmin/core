<?php

namespace Admin\Core\Tests\Feature\Model;

use Admin\Core\Tests\TestCase;
use Admin\Core\Tests\App\Models\Fields\FieldsType;

class ModelFieldsTypesTest extends TestCase
{
    private $model;

    protected function setUp() : void
    {
        parent::setUp();

        $this->model = new FieldsType;
    }

    /** @test */
    public function string()
    {
        $this->assertEquals($this->model->getField('string'), [
            'name' => 'my string field',
            'title' => 'this is my field description',
            'type' => 'string',
            'required' => true,
            'max' => '255',
        ]);
    }

    /** @test */
    public function text()
    {
        $this->assertEquals($this->model->getField('text'), [
            'name' => 'my text field',
            'type' => 'text',
            'required' => true,
        ]);
    }

    /** @test */
    public function longtext()
    {
        $this->assertEquals($this->model->getField('longtext'), [
            'name' => 'my longtext field',
            'type' => 'longtext',
            'required' => true,
        ]);
    }

    /** @test */
    public function integer()
    {
        $this->assertEquals($this->model->getField('integer'), [
            'name' => 'my integer field',
            'type' => 'integer',
            'required' => true,
            'integer' => true,
            'max' => '4294967295',
        ]);
    }

    /** @test */
    public function decimal()
    {
        $this->assertEquals($this->model->getField('decimal'), [
            'name' => 'my decimal field',
            'type' => 'decimal',
            'required' => true,
            'numeric' => true,
        ]);
    }

    /** @test */
    public function file()
    {
        $this->assertEquals($this->model->getField('file'), [
            'name' => 'my file field',
            'type' => 'file',
            'required' => true,
            'max' => '10240',
            'file' => true,
            'nullable' => true,
        ]);
    }

    /** @test */
    public function date()
    {
        $this->assertEquals($this->model->getField('date'), [
            'name' => 'my date field',
            'type' => 'date',
            'required' => true,
            'date_format' => 'd.m.Y',
            'nullable' => true,
        ]);
    }

    /** @test */
    public function datetime()
    {
        $this->assertEquals($this->model->getField('datetime'), [
            'name' => 'my datetime field',
            'type' => 'datetime',
            'required' => true,
            'date_format' => 'd.m.Y H:i',
            'nullable' => true,
        ]);
    }

    /** @test */
    public function time()
    {
        $this->assertEquals($this->model->getField('time'), [
            'name' => 'my time field',
            'type' => 'time',
            'required' => true,
            'date_format' => 'H:i',
            'nullable' => true,
        ]);
    }

    /** @test */
    public function checkbox()
    {
        $this->assertEquals($this->model->getField('checkbox'), [
            'name' => 'my checkbox field',
            'type' => 'checkbox',
            'boolean' => true,
        ]);
    }
}
