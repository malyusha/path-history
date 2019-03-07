<?php

namespace Malyusha\PathHistory\Tests;

class HasPathHistoryTest extends TestCase
{
    /**
     * @covers HasPathHistory::paths()
     */
    public function test_path_history_generates_on_each_action_of_model()
    {
        $product = new Models\Product();

        $product->setSlugAttribute('AH111');
        $product->save();

        $this->assertEquals(1, $product->paths()->count());
    }

    /**
     * @covers HasPathHistory::getCurrentPath()
     * @covers HasPathHistory::getPathHistory()
     */
    public function test_path_history_saves_history_of_written_paths()
    {
        $product = new Models\Product();
        $product->setSlugAttribute('testing');
        $product->save();
        $this->assertEquals('testing', $product->getCurrentPath());

        $product->setSlugAttribute('new-one');
        $product->save();
        $this->assertEquals('new-one', $product->getCurrentPath());

        $this->assertCount(2, $product->getPathHistory());
    }

    /**
     * @covers HasPathHistory::getPathHistory()
     */
    public function test_path_history_marks_previous_item_as_non_current()
    {
        $product = new Models\Product();
        $product->setSlugAttribute('testing');
        $product->save();
        $historyItem = $product->getPathHistory()->first();
        $this->assertTrue($historyItem->is_current);
        // Add new paths
        $product->setSlugAttribute('testing-new');
        $product->save();
        $this->assertFalse($historyItem->fresh()->is_current);
        $this->assertTrue($product->paths()->orderByDesc('id')->first()->is_current);
    }

    public function test_path_history_generates_parent_paths_correctly()
    {
        /**@var \Malyusha\PathHistory\Tests\Models\ProductCategory $rootCategory */
        $rootCategory = \Malyusha\PathHistory\Tests\Models\ProductCategory::create(['slug' => 'first-category']);
        $childCategory = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'nested']);
        $childCategory->parent()->associate($rootCategory)->save();
        $product = new \Malyusha\PathHistory\Tests\Models\Product();
        $product->setSlugAttribute('testing');
        $product->category()->associate($childCategory)->save();

        $this->assertEquals('first-category', $rootCategory->getCurrentPath());
        $this->assertEquals('first-category/nested', $childCategory->getCurrentPath());
        $this->assertEquals('first-category/nested/testing', $product->getCurrentPath());

        // Regenerate root path
        $rootCategory->slug = 'changed-slug';
        $rootCategory->save();
        $this->assertEquals('changed-slug', $rootCategory->getCurrentPath());
        $this->assertEquals('changed-slug/nested', $childCategory->getCurrentPath());
        $this->assertEquals('changed-slug/nested/testing', $product->getCurrentPath());
    }

    /**
     * Case 1
     */
    public function test_exception_case_1()
    {
        $this->withExpectedException(\Malyusha\PathHistory\Tests\Models\ProductWithoutParentPathRelation::class);
    }

    /**
     * Case 2
     */
    public function test_exception_case_2()
    {
        $this->withExpectedException(\Malyusha\PathHistory\Tests\Models\ProductWithIncorrectRelationType::class);
    }

    /**
     * Case 3
     */
    public function test_exception_case_3()
    {
        $this->withExpectedException(\Malyusha\PathHistory\Tests\Models\ProductReturningProductDetail::class, function (\Malyusha\PathHistory\Tests\Models\ProductReturningProductDetail $product) {
            $detail = new \Malyusha\PathHistory\Tests\Models\ProductDetail();
            $detail->save();
            $product->detail()->associate($detail);
        });
    }

    /**
     * Exceptional cases:
     * 1. If model, using HasPathHistory trait, neither provides method `shouldUseParentPaths` nor method returns
     * false, exception should be thrown, because of not provided parent relations to generate path on.
     * 2. If model, using HasPathHistory trait, describes relation method, but it's not returning valid
     * Illuminate\Relation instance, exception must be thrown.
     * 3. If related models returned by model don't use HasPathHistory trait, exception must be thrown.
     *
     * @param string $class
     * @param callable|null $cb
     */
    protected function withExpectedException(string $class, callable $cb = null)
    {
        $this->expectException(\Malyusha\PathHistory\Exceptions\PathHistoryException::class);

        $product = new $class();
        $product->setSlugAttribute('testing');
        if ($cb !== null) {
            $cb($product);
        }
        $product->save();
    }

    public function test_new_history_item_wont_be_created_if_slugs_are_equal()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'testing']);
        $category->save();

        $this->assertCount(1, $category->getPathHistory());
        $category->setSlugAttribute('new');

        $category->setSlugAttribute('testing');
        $category->save();
        $this->assertCount(1, $category->getPathHistory());
    }

    public function test_all_paths_are_deleted_after_model_deletion()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'testing']);
        $category->save();

        $category->update(['slug' => 'new']);
        $category->update(['slug' => 'another-one']);

        // Check that now category has 3 items in path history
        $this->assertEquals(3, $category->paths()->count());
        // Now, delete the category and all it's paths must be deleted too
        $category->delete();
        $this->assertCount(0, app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)->getAll());
    }

    /**
     * @covers HasPathHistory::getOldPaths()
     * @covers HasPathHistory::getPathHistory()
     */
    public function test_path_history_correctly_returned()
    {
        $product = new \Malyusha\PathHistory\Tests\Models\Product();
        foreach (['testing', 'new', 'another'] as $code) {
            $product->setSlugAttribute($code);
            $product->save();
        }

        // Now we're assuming product has 3 paths in history and 2 are old
        $this->assertCount(2, $product->getOldPaths());
        $this->assertCount(3, $product->getPathHistory());
    }

    /**
     * Asserts that magic field path will return full path of entity only if relation `currentPath` was loaded on model.
     * Otherwise value of `slug` attribute will be returned from model.
     */
    public function test_slug_returned_instead_of_full_path_if_relation_not_loaded()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'category']);
        $category->save();
        $category->children()->create(['slug' => 'sub-category']);

        $sub = \Malyusha\PathHistory\Tests\Models\ProductCategory::where('parent_id', $category->id)->first();

        $this->assertEquals('sub-category', $sub->path);
        $sub->load('currentPath');
        $this->assertEquals('category/sub-category', $sub->path);
    }

    /**
     * Asserts that currently set instance of path history is valid and correct for model using trait.
     *
     * @covers HasPathHistory::getCurrentPathInstance()
     */
    public function test_current_path_history_instance_correctly_returned()
    {
        $product = new \Malyusha\PathHistory\Tests\Models\Product();
        $product->setSlugAttribute('testing');
        $product->save();
        $phInstance = $product->getCurrentPathInstance();
        $this->assertInstanceOf(get_class(app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)), $phInstance);
        $this->assertEquals('testing', $phInstance->link);
    }

    /**
     * @covers HasPathHistory::getChangersAttributes()
     */
    public function test_additional_changers_attributes_are_added()
    {
        $product = new Models\Product();

        $this->assertEquals([$product->slugAttribute(), 'category_id'], $product->getChangersAttributes());
    }

    public function test_it_has_relation_returned()
    {
        $pathHistory = app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $pathHistory->related());
    }
}