<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Models;

class ProductWithoutParentPathRelation extends Product
{
    protected $parentPathRelation;
}