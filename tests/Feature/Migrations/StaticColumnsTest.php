<?php

namespace Admin\Core\Tests\Feature\Migrations;

use AdminCore;
use Admin\Core\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Admin\Core\Tests\Concerns\DropDatabase;
use Admin\Core\Tests\App\Models\Articles\Article;

class StaticColumnsTest extends TestCase
{
    use DropDatabase;

    public function setUp() : void
    {
        parent::setUp();

        AdminCore::registerModel([
            Article::class,
        ]);

        $this->artisan('admin:migrate');

        $this->setSchema(DB::getSchemaBuilder());
    }

    /** @test */
    public function test_slug_column()
    {
        $this->assertColumnExists('articles', 'slug')
             ->assertColumnType('articles', 'slug', 'string')
             ->assertColumnNotNull('articles', 'slug', false)
             ->assertColumnLength('articles', 'slug', 255);
    }

    /** @test */
    public function test_published_at_column()
    {
        $this->assertColumnExists('articles', 'published_at')
             ->assertColumnType('articles', 'published_at', 'datetime')
             ->assertColumnNotNull('articles', 'published_at', false);
    }

    /** @test */
    public function test_updated_at_column()
    {
        $this->assertColumnExists('articles', 'updated_at')
             ->assertColumnType('articles', 'updated_at', 'datetime')
             ->assertColumnNotNull('articles', 'updated_at', false);
    }

    /** @test */
    public function test_created_at_column()
    {
        $this->assertColumnExists('articles', 'created_at')
             ->assertColumnType('articles', 'created_at', 'datetime')
             ->assertColumnNotNull('articles', 'created_at', false);
    }

    /** @test */
    public function test_deleted_at_column()
    {
        $this->assertColumnExists('articles', 'deleted_at')
             ->assertColumnType('articles', 'deleted_at', 'datetime')
             ->assertColumnNotNull('articles', 'deleted_at', false);
    }
}
