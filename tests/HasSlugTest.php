<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests;

class HasSlugTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers HasSlug::slugAttribute()
     */
    public function test_it_returns_correct_slug_attribute_name()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory();
        $product = new \Malyusha\PathHistory\Tests\Models\Product();
        $this->assertEquals('slug', $category->slugAttribute(), 'Slug attribute name of category must be `slug`');
        $this->assertEquals('vendor_code', $product->slugAttribute(), 'Slug attribute name of product must be `vendor_code`');
    }

    /**
     * @covers HasSlug::isExternalLink()
     * @covers HasSlug::isExternal()
     */
    public function test_it_determines_external_link_correctly()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'https://example.com/test-slug-of-category']);

        $this->assertTrue($category->isExternal());
        $category->setSlugAttribute('internal-slug');
        $this->assertFalse($category->isExternal());
    }

    /**
     * @covers HasSlug::isStubLink()
     */
    public function test_it_determines_stub_links_correctly()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => '#dummy']);
        $this->assertTrue($category->isStub());
    }

    /**
     * @covers HasSlug::setSlugAttribute()
     * @covers HasSlug::makeSlug()
     */
    public function test_it_makes_slug_automatically()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory();
        $category->setSlugAttribute('New category');
        $this->assertEquals('new-category', $category->getSlug());
    }
}