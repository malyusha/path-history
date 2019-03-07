<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Models;

class Product extends \Illuminate\Database\Eloquent\Model
{
    use \Malyusha\PathHistory\HasPathHistory;

    protected $table = 'products';

    protected $slugAttribute = 'vendor_code';

    protected $parentPathRelation = 'category';

    protected $updatePathOnChangeAttributes = ['category_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}