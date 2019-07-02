<?php

namespace Admin\Core\Tests\Feature\Bootloader;

use AdminCore;
use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Tests\App\OtherModels\Blog;
use Admin\Core\Tests\TestCase;

class AdminCoreTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    /** @test */
    public function no_models_are_available()
    {
        $this->assertEquals(AdminCore::boot(), []);
    }

    /** @test */
    public function models_from_config_directory_are_available()
    {
        $this->registerAllAdminModels();

        $this->assertEquals(AdminCore::boot(), [
            '2019-05-04 12:10:04' => 'Admin\Core\Tests\App\Models\Articles\Article',
            '2019-05-04 12:10:15' => 'Admin\Core\Tests\App\Models\Articles\ArticlesComment'
        ]);
    }

    /** @test */
    public function models_loaded_dynamically_from_package()
    {
        //Register dynamically admin model
        AdminCore::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Core\Tests\App\OtherModels');

        $this->assertEquals(AdminCore::getAdminModelNamespaces(), [
            '2019-05-03 13:10:04' => 'Admin\Core\Tests\App\OtherModels\Blog',
            '2019-05-03 14:11:02' => 'Admin\Core\Tests\App\OtherModels\BlogsImage'
        ]);
    }

    /** @test */
    public function get_model_by_table()
    {
        AdminCore::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Core\Tests\App\OtherModels');

        $this->assertNull(AdminCore::getModelByTable('blog'));
        $this->assertInstanceOf(AdminModel::class, AdminCore::getModelByTable('blogs'));
    }

    /** @test */
    public function get_model_by_classname()
    {
        AdminCore::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Core\Tests\App\OtherModels');

        $this->assertNull(AdminCore::getModel('blogs'));
        $this->assertInstanceOf(AdminModel::class, AdminCore::getModel('blog'));
        $this->assertInstanceOf(AdminModel::class, AdminCore::getModel('Blog'));
    }

    /** @test */
    public function check_if_is_admin_model()
    {
        $this->assertTrue(AdminCore::isAdminModel(new Blog));
    }


    // /** @test */
    // public function check_if_has_admin_model()
    // {
    //     AdminCore::registerAdminModels($this->getAppPath('OtherModels'), 'Admin\Core\Tests\App\OtherModels');

    //     $this->assertTrue(AdminCore::hasAdminModel('Blog'));
    //     $this->assertTrue(AdminCore::hasAdminModel('BlogsImage'));
    //     $this->assertFalse(AdminCore::hasAdminModel('BlogsImages'));
    // }
}
