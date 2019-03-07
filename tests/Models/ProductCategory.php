<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Models;

class ProductCategory extends \Illuminate\Database\Eloquent\Model
{
    use \Malyusha\PathHistory\HasPathHistory;

    protected $table = 'product_categories';

    protected $fillable = ['slug'];

    protected $parentPathRelation = 'parent';

    protected $updatePathOnChangeAttributes = ['parent_id'];

    protected $descendantRetrievers = [
        \Malyusha\PathHistory\Tests\Decendants\ProductsOfCategory::class,
        \Malyusha\PathHistory\Tests\Decendants\ChildrenCategories::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function shouldUseParentPaths(): bool
    {
        return ! $this->isRoot();
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}