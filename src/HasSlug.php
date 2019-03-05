<?php
/**
 * This file is a part of Laravel Path History package.
 * Email:       mii18@yandex.ru
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory;

trait HasSlug
{
    /**
     * Default attribute for path generation.
     *
     * @var string
     */
    protected static $defaultSlugAttribute = 'slug';

    /**
     * Returns attribute responsible for path generation.
     *
     * @return string
     */
    public function slugAttribute(): string
    {
        return property_exists($this, 'slugAttribute') ? $this->slugAttribute : static::$defaultSlugAttribute;
    }

    /**
     * Checks if entity path is external.
     *
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->isExternalLink($this->getSlug());
    }

    /**
     * Checks whether link is external.
     *
     * @param string $link
     *
     * @return bool
     */
    public function isExternalLink($link): bool
    {
        if ($link === null) {
            return true;
        }

        return starts_with($link, ['http://', 'https://']);
    }

    /**
     * Checks whether link is stub (#).
     *
     * @param string $link
     *
     * @return bool
     */
    public function isStubLink(string $link): bool
    {
        return starts_with($link, '#');
    }

    /**
     * @return null|string
     */
    public function getSlug()
    {
        return $this->{$this->slugAttribute()};
    }

    /**
     * Setter for default slug attribute.
     *
     * @param $slug
     *
     * @return void
     */
    public function setSlugAttribute($slug)
    {
        if ($slug) {
            $this->makeSlug('slug', $slug);
        }
    }

    public function isSlug(): bool
    {
        return $this->isStubLink($this->getSlug());
    }

    /**
     * Creates slug in attributes.
     *
     * @param string $attribute
     * @param $value
     */
    protected function makeSlug(string $attribute, $value)
    {
        if (! $this->isExternalLink($value) && ! $this->isStubLink($value)) {
            $value = str_slug($value);
        }

        $this->attributes[$attribute] = $value;
    }
}
