<?php
/**
 * This file is a part of Kodix Homer project.
 * Email:       support@kodix.ru
 * Company:     Kodix LLC <https://kodix.com>
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Examples\Basic;

class NewsItem extends \Illuminate\Database\Eloquent\Model
{
    // Override property slugAttribute to custom
    protected $slugAttribute = 'code';

    // Set parent path relation as our news item's url must be prefixed with {category_slug}/
    protected $parentPathRelation = 'category';

    // We need to update our news on category change
    protected $updatePathOnChangeAttributes = ['category_id'];

    /**
     * Category relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }
}