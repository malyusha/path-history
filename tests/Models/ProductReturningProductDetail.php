<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Models;

class ProductReturningProductDetail extends \Illuminate\Database\Eloquent\Model
{
    use \Malyusha\PathHistory\HasPathHistory;

    protected $table = 'products';

    protected $slugAttribute = 'vendor_code';

    protected $parentPathRelation = 'detail';

    public function detail()
    {
        return $this->belongsTo(ProductDetail::class, 'detail_id');
    }
}