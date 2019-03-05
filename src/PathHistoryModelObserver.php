<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory;

use Malyusha\PathHistory\Contracts\PathHistoryContract;

class PathHistoryModelObserver
{
    public function saving(PathHistoryContract $contract)
    {
        if ($contract->isSelfRelated()) {
            $contract->setCurrent(false);
        }
    }

    public function created(PathHistoryContract $contract)
    {
        $contract->unmarkCurrent();
    }

    public function deleted(PathHistoryContract $contract)
    {
        $contract->markNextAsCurrent();
    }

    public function deleting(PathHistoryContract $contract)
    {
        $contract->deleteSelfRelated();
    }
}
