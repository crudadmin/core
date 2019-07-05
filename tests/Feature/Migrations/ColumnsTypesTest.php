<?php

namespace Admin\Core\Tests\Feature\Migrations;

use Admin\Core\Tests\App\Models\Fields\FieldsType;
use Admin\Core\Tests\Concerns\DropDatabase;
use Admin\Core\Tests\TestCase;

class ColumnsTypesTest extends TestCase
{
    use DropDatabase;

    public function setUp() : void
    {
        parent::setUp();

        $this->artisan('admin:migrate');
    }

    /*
     * Load all admin models
     */
    protected $loadAllAdminModels = true;

    /** @test */
    public function test_string_column()
    {
        $this->assertColumnExists(FieldsType::class, 'string')
             ->assertColumnType(FieldsType::class, 'string', 'string')
             ->assertColumnNotNull(FieldsType::class, 'string', true)
             ->assertColumnLength(FieldsType::class, 'string', 255);
    }

    /** @test */
    public function test_text_column()
    {
        $this->assertColumnExists(FieldsType::class, 'text')
             ->assertColumnType(FieldsType::class, 'text', 'text')
             ->assertColumnNotNull(FieldsType::class, 'text', true);
    }

    /** @test */
    public function test_editor_column()
    {
        $this->assertColumnExists(FieldsType::class, 'editor')
             ->assertColumnType(FieldsType::class, 'editor', 'text')
             ->assertColumnNotNull(FieldsType::class, 'editor', true);
    }

    /** @test */
    public function test_select_column()
    {
        $this->assertColumnExists(FieldsType::class, 'select')
             ->assertColumnType(FieldsType::class, 'select', 'string')
             ->assertColumnNotNull(FieldsType::class, 'select', true)
             ->assertColumnLength(FieldsType::class, 'select', 255);
    }

    /** @test */
    public function test_integer_column()
    {
        $this->assertColumnExists(FieldsType::class, 'integer')
             ->assertColumnType(FieldsType::class, 'integer', 'integer')
             ->assertColumnNotNull(FieldsType::class, 'integer', true)
             ->assertColumnUnsigned(FieldsType::class, 'integer', false);
    }

    /** @test */
    public function test_decimal_column()
    {
        $this->assertColumnExists(FieldsType::class, 'decimal')
             ->assertColumnType(FieldsType::class, 'decimal', 'decimal')
             ->assertColumnNotNull(FieldsType::class, 'decimal', true)
             ->assertColumnUnsigned(FieldsType::class, 'decimal', false);
    }

    /** @test */
    public function test_file_column()
    {
        $this->assertColumnExists(FieldsType::class, 'file')
             ->assertColumnType(FieldsType::class, 'file', 'string')
             ->assertColumnNotNull(FieldsType::class, 'file', true)
             ->assertColumnLength(FieldsType::class, 'file', 255);
    }

    /** @test */
    public function test_password_column()
    {
        $this->assertColumnExists(FieldsType::class, 'password')
             ->assertColumnType(FieldsType::class, 'password', 'string')
             ->assertColumnNotNull(FieldsType::class, 'password', true)
             ->assertColumnLength(FieldsType::class, 'password', 255);
    }

    /** @test */
    public function test_date_column()
    {
        $this->assertColumnExists(FieldsType::class, 'date')
             ->assertColumnType(FieldsType::class, 'date', 'date')
             ->assertColumnNotNull(FieldsType::class, 'date', false);
    }

    /** @test */
    public function test_datetime_column()
    {
        $this->assertColumnExists(FieldsType::class, 'datetime')
             ->assertColumnType(FieldsType::class, 'datetime', 'datetime')
             ->assertColumnNotNull(FieldsType::class, 'datetime', false);
    }

    /** @test */
    public function test_time_column()
    {
        $this->assertColumnExists(FieldsType::class, 'time')
             ->assertColumnType(FieldsType::class, 'time', 'time')
             ->assertColumnNotNull(FieldsType::class, 'time', false);
    }

    /** @test */
    public function test_checkbox_column()
    {
        $this->assertColumnExists(FieldsType::class, 'checkbox')
             ->assertColumnType(FieldsType::class, 'checkbox', 'boolean')
             ->assertColumnNotNull(FieldsType::class, 'checkbox', false);
    }

    /** @test */
    public function test_radio_column()
    {
        $this->assertColumnExists(FieldsType::class, 'radio')
             ->assertColumnType(FieldsType::class, 'radio', 'string')
             ->assertColumnNotNull(FieldsType::class, 'radio', true)
             ->assertColumnLength(FieldsType::class, 'string', 255);
    }
}
