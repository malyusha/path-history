<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests;

class ResolverTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_resolver_resolves_controller_for_path_correctly()
    {
        $this->createTestEntities();
        $successShopPaths = ['boots/black/black-1', 'sweaters/women/women-1', 'shoes/sneakers/sneakers-4'];
        $notFoundShopPaths = ['not-found', 'non-existing'];
        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $resolver = new \Malyusha\PathHistory\Http\Resolver();
        foreach ($successShopPaths as $path) {
            $request->shouldReceive('path')->andReturn('shop/'.$path);
            $this->assertEquals('shop_controller_result', $resolver($request, $path));
        }

        foreach ($notFoundShopPaths as $path) {
            $notFound = false;
            $request->shouldReceive('path')->andReturn('shop/'.$path);
            try {
                $resolver($request, $path);
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception) {
                $notFound = true;
            } finally {
                $this->assertTrue($notFound);
            }
        }
    }

    protected function createTestEntities()
    {
        // For shop
        $categories = ['boots', 'sweaters', 'shoes'];
        $subCategories = [
            ['black', 'blue', 'white', 'brown'],
            ['man', 'women', 'children'],
            ['high-heels', 'sneakers'],
        ];

        $subs = [];

        foreach ($categories as $ix => $category) {
            $categories[$ix] = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => $category]);
            $categories[$ix]->save();
        }

        foreach ($subCategories as $ix => $codes) {
            foreach ($codes as $code) {
                $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => $code]);
                $category->parent()->associate($categories[$ix]);
                $category->save();
                $subs[] = $category;
            }
        }

        foreach ($subs as $category) {
            foreach (range(0, 20) as $c) {
                $product = new \Malyusha\PathHistory\Tests\Models\Product();
                $product->setSlugAttribute($category->slug.'-'.$c);
                $product->category()->associate($category)->save();
            }
        }
    }
}