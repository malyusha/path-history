<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests;

class PathHistoryModelTest extends TestCase
{
    /**
     * @covers PathHistoryContract::getByLink()
     */
    public function test_it_returns_instance_for_given_link()
    {
        $product = new \Malyusha\PathHistory\Tests\Models\Product();
        $product->setSlugAttribute('testing');
        $product->save();
        $pathHistoryInstance = $product->getCurrentPathInstance();
        $this->assertEquals($pathHistoryInstance, app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)->getByLink('testing'));
        // Should fail to find row if not matched types provided as second argument
        $this->assertNull(app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)->getByLink('testing', [(new \Malyusha\PathHistory\Tests\Models\ProductCategory)->getMorphClass()]));
        // And again, should find row because correct list type given as second argument
        $this->assertEquals($pathHistoryInstance, app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)->getByLink('testing', [$product->getMorphClass()]));
    }

    /**
     * @covers PathHistoryContract::isSelfRelated()
     * @covers PathHistoryContract::deleteSelfRelated()
     */
    public function test_self_related()
    {
        $root = new \Malyusha\PathHistory\Models\PathHistory();
        $root->link = 'testing';
        $root->save();
        $redirect1 = new \Malyusha\PathHistory\Models\PathHistory;
        $redirect2 = new \Malyusha\PathHistory\Models\PathHistory;
        $redirect1->link = 'redirect-from-1';
        $redirect2->link = 'redirect-from-2';
        $root->redirects()->saveMany([$redirect1, $redirect2]);

        $this->assertTrue($redirect1->isSelfRelated());

        $root->deleteSelfRelated();

        $this->assertCount(1, app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class)->getAll());
    }

    /**
     * @covers PathHistoryContract::getRedirectForLink()
     */
    public function test_it_returns_correct_redirect_link_for_given_paths()
    {
        /**@var []Malyusha\PathHistory\Tests\Models\Product $created */
        $created = [];
        for ($i = 0; $i < 5; $i++) {
            $p = new \Malyusha\PathHistory\Tests\Models\Product;
            $p->setSlugAttribute('testing_'.$i);
            $p->save();
            $created[] = $p;
        }

        foreach ($created as $k => $product) {
            $product->setSlugAttribute('new_testing_'.$k);
            $product->save();
        }

        $instance = app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class);

        foreach ($created as $k => $product) {
            $this->assertEquals('new-testing-'.$k, $instance->getRedirectForLink('testing-'.$k));
        }
    }
}