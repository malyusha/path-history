<?php
/**
 * This file is a part of Kodix Homer project.
 * Email:       support@kodix.ru
 * Company:     Kodix LLC <https://kodix.com>
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Examples\Basic;

class NewsCategory extends \Illuminate\Database\Eloquent\Model
{
    use \Malyusha\PathHistory\HasPathHistory;

    // Define retrievers to load related news
    protected $descendantRetrievers = [NewsOfCategory::class];

    public function defaultShouldUseParentPaths(): bool
    {
        // News category doesn't have any parent relation
        return false;
    }

    /**
     * News relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function news(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NewsItem::class, 'news', 'category_id');
    }
}