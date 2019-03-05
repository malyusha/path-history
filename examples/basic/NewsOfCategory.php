<?php

namespace Malyusha\PathHistory\Examples\Basic;

class NewsOfCategory implements \Malyusha\PathHistory\Contracts\DescendantsRetrieverContract
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
        // Just return news of given category
        return $parent->news()->get();
    }
}
