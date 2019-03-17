<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();

        // setting up
        static::setUpDatabase();
    }

    /**
     * Prepares database to be testable.
     */
    protected static function setUpDatabase()
    {
        static::createPathHistoryTable();
        static::createTables();
    }

    /**
     * Creates tables from migrations for testing.
     */
    protected static function createTables()
    {
        foreach (static::getMigrationsToCreate() as $tableName => $cb) {
            \Illuminate\Support\Facades\Schema::create($tableName, $cb);
        }
    }

    public function getPackageProviders($app)
    {
        return [
            \Malyusha\PathHistory\PathHistoryServiceProvider::class,
        ];
    }

    /**
     * Returns migrations to run.
     *
     * @return array
     */
    protected static function getMigrationsToCreate(): array
    {
        return [
            'products'           => function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('vendor_code', 100)->unique();
                $table->timestamps();
                $table->unsignedInteger('category_id')->nullable();
                $table->unsignedInteger('detail_id')->nullable();
                $table->foreign('category_id')
                    ->references('id')
                    ->on('product_categories')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
                $table->foreign('detail_id')
                    ->references('id')
                    ->on('product_details')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            },
            'product_categories' => function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('parent_id')->nullable();
                $table->string('name')->nullable();
                $table->string('slug', 100)->unique();
                $table->softDeletes();
                $table->timestamps();
            },
            'product_details'    => function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            },
        ];
    }

    /**
     * Creates path history table from migration package file.
     */
    protected static function createPathHistoryTable()
    {
        include_once __DIR__.'/../database/migrations/create_path_history.php.stub';

        (new \CreatePathHistory)->up();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = include_once __DIR__.'/../config/path_history.php';

        $app['config']->set('path_history', $config);
        $app['config']->set('path_history.paths', [
            [
                'prefix'     => 'shop',
                'types'      => [
                    \Malyusha\PathHistory\Tests\Models\ProductCategory::class,
                    \Malyusha\PathHistory\Tests\Models\Product::class,
                ],
                'controller' => \Malyusha\PathHistory\Tests\Controllers\ShopController::class,
            ],
            [
                'prefix' => 'another',
                'types'  => [
                    \Malyusha\PathHistory\Tests\Models\ProductCategory::class => \Malyusha\PathHistory\Tests\Controllers\AnotherController::class,
                ],
            ],
            [
                'prefix'     => 'invalid',
                'types'      => [\Malyusha\PathHistory\Tests\Models\Product::class],
                'controller' => \Malyusha\PathHistory\Tests\Controllers\InvalidController::class,
            ],
        ]);
    }
}