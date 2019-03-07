<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Decendants;

class ChildrenCategories implements \Malyusha\PathHistory\Contracts\DescendantsRetrieverContract
{

    /**
     * Returns collection of descendants for model. Those descendants must use HasPathHistory trait to be able
     * to create or update path history.
     *
     * @param \Illuminate\Database\Eloquent\Model $parent Descendants depends from this model.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDescendants(\Illuminate\Database\Eloquent\Model $parent): \Illuminate\Support\Collection
    {
        return $parent->children()->get();
    }
}