<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Collections;

use Malyusha\PathHistory\Contracts\PathHistoryContract;

class RedirectsCollection extends \Illuminate\Database\Eloquent\Collection
{
    /**
     * @var \Malyusha\PathHistory\Contracts\PathHistoryContract
     */
    protected $parent;

    public function __construct(PathHistoryContract $parent, $items = [])
    {
        $this->parent = $parent;

        parent::__construct($items);
    }
}
