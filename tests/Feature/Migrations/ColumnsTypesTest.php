<?php

namespace Admin\Core\Tests\Feature\Migrations;

use AdminCore;
use Admin\Core\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Admin\Core\Tests\Concerns\DropDatabase;
use Admin\Core\Tests\App\Models\Articles\Article;
use Admin\Core\Tests\App\Models\Fields\FieldsType;
use Admin\Core\Tests\App\Models\Fields\FieldsRelation;

class ColumnsTypesTest extends TestCase
{
    use DropDatabase;

    public function setUp() : void
    {
        parent::setUp();

        AdminCore::registerModel([
            Article::class,
            FieldsRelation::class,
            FieldsType::class,
        ]);

        $this->artisan('admin:migrate');

        $this->setSchema(DB::getSchemaBuilder());
    }

    /** @test */
    public function test_string_column()
    {
        $this->assertColumnExists('fields_types', 'string')
             ->assertColumnType('fields_types', 'string', 'string')
             ->assertColumnNotNull('fields_types', 'string', true)
             ->assertColumnLength('fields_types', 'string', 255);
    }

    /** @test */
    public function test_text_column()
    {
        $this->assertColumnExists('fields_types', 'text')
             ->assertColumnType('fields_types', 'text', 'text')
             ->assertColumnNotNull('fields_types', 'text', true);
    }

    /** @test */
    public function test_longtext_column()
    {
        $this->assertColumnExists('fields_types', 'longtext')
             ->assertColumnType('fields_types', 'longtext', 'text')
             ->assertColumnNotNull('fields_types', 'longtext', true);
    }

    /** @test */
    public function test_integer_column()
    {
        $this->assertColumnExists('fields_types', 'integer')
             ->assertColumnType('fields_types', 'integer', 'integer')
             ->assertColumnNotNull('fields_types', 'integer', true)
             ->assertColumnUnsigned('fields_types', 'integer', false);
    }

    /** @test */
    public function test_decimal_column()
    {
        $this->assertColumnExists('fields_types', 'decimal')
             ->assertColumnType('fields_types', 'decimal', 'decimal')
             ->assertColumnNotNull('fields_types', 'decimal', true)
             ->assertColumnUnsigned('fields_types', 'decimal', false);
    }

    /** @test */
    public function test_file_column()
    {
        $this->assertColumnExists('fields_types', 'file')
             ->assertColumnType('fields_types', 'file', 'string')
             ->assertColumnNotNull('fields_types', 'file', true)
             ->assertColumnLength('fields_types', 'file', 255);
    }

    /** @test */
    public function test_date_column()
    {
        $this->assertColumnExists('fields_types', 'date')
             ->assertColumnType('fields_types', 'date', 'date')
             ->assertColumnNotNull('fields_types', 'date', false);
    }

    /** @test */
    public function test_datetime_column()
    {
        $this->assertColumnExists('fields_types', 'datetime')
             ->assertColumnType('fields_types', 'datetime', 'datetime')
             ->assertColumnNotNull('fields_types', 'datetime', false);
    }

    /** @test */
    public function test_time_column()
    {
        $this->assertColumnExists('fields_types', 'time')
             ->assertColumnType('fields_types', 'time', 'time')
             ->assertColumnNotNull('fields_types', 'time', false);
    }

    /** @test */
    public function test_checkbox_column()
    {
        $this->assertColumnExists('fields_types', 'checkbox')
             ->assertColumnType('fields_types', 'checkbox', 'boolean')
             ->assertColumnNotNull('fields_types', 'checkbox', false);
    }

    /** @test */
    public function test_belongs_to_column()
    {
        $this->assertColumnExists('fields_relations', 'article_id')
             ->assertColumnType('fields_relations', 'article_id', 'integer')
             ->assertColumnNotNull('fields_relations', 'article_id', true)
             ->assertColumnUnsigned('fields_relations', 'article_id', true)
             ->assertHasForeignKey('fields_relations', 'fields_relations_article_id_foreign');
    }

    /** @test */
    public function test_belongs_to_many_column()
    {
        $scheme = $this->getModelClass(FieldsRelation::class)->getSchema();

        $this->assertTableExists('article_fields_relation_multiple')
             ->assertColumnExists('article_fields_relation_multiple', 'fields_relation_id')
             ->assertColumnExists('article_fields_relation_multiple', 'article_id')
             ->assertHasForeignKey('article_fields_relation_multiple', 'fk_aefsrnme_fields_relation_id')
             ->assertHasForeignKey('article_fields_relation_multiple', 'fk_aefsrnme_article_id');
    }
}
