<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests;

class ModelsObserverTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }

    protected function getMockModel()
    {
        $model = \Mockery::mock(\Malyusha\PathHistory\Contracts\PathHistoryContract::class);

        return $model;
    }

    /**
     * @covers \Malyusha\PathHistory\PathHistoryModelObserver
     */
    public function test_observer_is_working()
    {
        $category = new \Malyusha\PathHistory\Tests\Models\ProductCategory(['slug' => 'new-category']);

        $observer = \Mockery::mock(\Malyusha\PathHistory\PathHistoryModelObserver::class);
        $phModel = app(\Malyusha\PathHistory\Contracts\PathHistoryContract::class);
        // To prevent new object creation when dispatcher fires an event on observer, we'll add this object to
        // application container
        $this->app->bind(\Malyusha\PathHistory\PathHistoryModelObserver::class, function () use ($observer) {
            return $observer;
        });

        $observer->shouldReceive('saving', 'created', 'deleting', 'deleted')->once();

        // set previously set observables to empty array to prevent double call
        $phModel::flushEventListeners();
        $phModel::observe(\Malyusha\PathHistory\PathHistoryModelObserver::class);
        // Calling created and saving methods in observer
        $category->save();
        // Calling deleted and deleting in observer
        $category->delete();
    }

    /**
     * @covers \Malyusha\PathHistory\PathHistoryModelObserver::saving()
     */
    public function test_saving_event_callback()
    {
        $mock = $this->getMockModel();
        $mock->shouldReceive('isSelfRelated')->andReturnTrue();
        $mock->shouldReceive('setCurrent')->with(false);

        $observer = new \Malyusha\PathHistory\PathHistoryModelObserver();
        $observer->saving($mock);
    }

    /**
     * @covers \Malyusha\PathHistory\PathHistoryModelObserver::created()
     */
    public function test_created_event_callback()
    {
        $mock = $this->getMockModel();
        $mock->shouldReceive('unmarkCurrent')->andReturnUndefined();
        $observer = new \Malyusha\PathHistory\PathHistoryModelObserver();
        $observer->created($mock);
    }

    /**
     * @covers \Malyusha\PathHistory\PathHistoryModelObserver::created()
     */
    public function test_deleting_event_callback()
    {
        $mock = $this->getMockModel();
        $mock->shouldReceive('deleteSelfRelated');
        $observer = new \Malyusha\PathHistory\PathHistoryModelObserver();
        $observer->deleting($mock);
    }

    /**
     * @covers \Malyusha\PathHistory\PathHistoryModelObserver::created()
     */
    public function test_deleted_event_callback()
    {
        $mock = $this->getMockModel();
        $mock->shouldReceive('markNextAsCurrent');
        $observer = new \Malyusha\PathHistory\PathHistoryModelObserver();
        $observer->deleted($mock);
    }
}