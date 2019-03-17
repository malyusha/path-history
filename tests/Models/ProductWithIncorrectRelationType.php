<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Models;

class ProductWithIncorrectRelationType extends Product
{
    protected $parentPathRelation = 'invalidRelation';

    public function invalidRelation(): array
    {
        return [];
    }
}