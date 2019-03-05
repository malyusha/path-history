<?php

namespace Malyusha\PathHistory\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Malyusha\PathHistory\Contracts\PathHistoryContract;

class PathHistory extends \Illuminate\Database\Eloquent\Model implements PathHistoryContract
{
    protected $fillable = [
        'link',
        'is_current',
    ];

    protected $casts = [
        'is_current' => true,
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('path_history.table'));

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function related(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function redirects(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(static::class, 'related');
    }

    /**
     * Returns redirect path for given link. Redirects are paths that have the same related fields.
     * The last of instances is current parent of others.
     *
     * @param string $link
     *
     * @return string|null
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public function getRedirectForLink(string $link)
    {
        $typesMap = \Malyusha\PathHistory\Config::getNormalizedPaths();

        $selfRedirect = static::where(function ($query) use ($link) {
            $query->where('link', $link)->orWhere('link', '/'.$link);
        })->with('related')->where('is_current', false)->where('related_type', $this->getMorphClass())->first();
        if ($selfRedirect !== null && $selfRedirect->related !== null) {
            $config = '';
            $link = str_replace_first('/', '', $selfRedirect->related->link);

            if ($selfRedirect->related->related_type) {
                $config = array_get($typesMap, $selfRedirect->related->related_type, '');
            }

            return $config.'/'.$link;
        }

        // Types to optimize search constraints
        $types = [];
        // Prefix will be prepended to final link if it found
        $prefix = '';
        foreach ($typesMap as $prefix => $map) {
            // If link doesn't start with prefix that defined in configuration we've nothing to do.
            if (! starts_with($link, $p = $prefix.'/')) {
                continue;
            }

            // Otherwise we need to find prefix for the link and trim it to search, as database value doesn't contain
            // prefix
            $prefix = $p;
            $types = array_keys($map);
            // Remove prefix from link
            $link = str_replace_first($p, '', $link);
        }

        // Search for the first NOT CURRENT path instance
        $path = $this->queryForLink($link, false, $types)->first();

        if ($path === null) {
            // We don't need redirects because there are no links yet
            return null;
        }

        // Find actual current path link to redirect
        $actual = $path->current()->first();
        if ($actual === null || $path->link === $actual->link) {
            // If there is no actual path or links of previous and current are equal we can't return redirect link
            // because it's not valid redirect link
            return null;
        }

        // Return link with matched prefix prepended
        return $prefix.$actual->link;
    }

    /**
     * @param string $link
     *
     * @param array $types Specific related types to search in.
     *
     * @return PathHistoryContract|null
     */
    public function getByLink(string $link, array $types = [])
    {
        return $this->queryForLink($link, true, $types)->first();
    }

    /**
     * @param string $link
     * @param bool $current
     * @param array $types
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryForLink(string $link, bool $current, array $types = [])
    {
        $query = $this->where(function ($query) use ($link) {
            $query->where('link', '=', $link)->orWhere('link', '=', '/'.$link);
        })->where('is_current', $current);

        if (count($types) > 0) {
            $query->whereIn('related_type', static::getMorphTypesMap($types));
        }

        return $query;
    }

    /**
     * Returns array of entities types. If any of values of given types are eloquent models their morph type will
     * be in resulting array, otherwise value as it was passed will be set.
     *
     * @param array $types
     *
     * @return array
     */
    public static function getMorphTypesMap(array $types): array
    {
        return array_unique(array_map(function ($type) {
            return Relation::getMorphedModel($type) ?? (new $type)->getMorphClass();
        }, $types));
    }

    /**
     * Unmarks all history as not current after new instance creation.
     *
     * @return void
     */
    public function unmarkCurrent()
    {
        if (! $this->isSelfRelated()) {
            $this->history()->update(['is_current' => false]);
        }
    }

    /**
     * @return bool
     */
    public function isSelfRelated(): bool
    {
        return $this->related_type === static::getMorphClass() && $this->related_type !== null;
    }

    /**
     * Returns instances of the same related.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function history(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('related_type', $this->related_type)
            ->where('is_current', true)
            ->where('related_id', $this->related_id)
            ->whereNotNull('related_id')
            ->orderByDesc('id')
            ->where('id', '!=', $this->id);
    }

    public function current()
    {
        return $this->history()->take(1);
    }

    /**
     * Marks next instance after current as current.
     *
     * @return mixed
     */
    public function markNextAsCurrent()
    {
        $instance = $this->history()->first();

        if ($instance !== null) {
            $instance->is_current = true;
            $instance->save();
        }
    }

    /**
     * Deletes self-related path instances.
     *
     * @return void
     */
    public function deleteSelfRelated()
    {
        if (! $this->isSelfRelated()) {
            $this->redirects()->delete();
        }
    }

    /**
     * Marks path as not current.
     *
     * @param bool $current
     *
     * @return mixed
     */
    public function setCurrent(bool $current = true)
    {
        $this->is_current = $current;
    }
}
