<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Contracts;

interface PathHistoryContract
{
    /**
     * Return relation for related entities paths.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function related(): \Illuminate\Database\Eloquent\Relations\MorphTo;

    /**
     * Unmarks all history as not current after new instance creation.
     *
     * @return void
     */
    public function unmarkCurrent();

    /**
     * Marks next instance after current as current.
     *
     * @return mixed
     */
    public function markNextAsCurrent();

    /**
     * Returns redirect for given path. Redirects are paths that have the same related fields.
     * The last of instances is current parent of others.
     *
     * @param string $link
     *
     * @return string|null
     */
    public function getRedirectForLink(string $link);

    /**
     * Returns PathHistory instance for given link and types (optional).
     *
     * @param string $link
     *
     * @param array $types Specific related types to search in.
     *
     * @return PathHistoryContract|null
     */
    public function getByLink(string $link, array $types = []);

    /**
     * Deletes self-related path instances.
     *
     * @return mixed
     */
    public function deleteSelfRelated();

    /**
     * Checks if path is self-related.
     *
     * @return bool
     */
    public function isSelfRelated(): bool;

    /**
     * Marks path as current/not_current.
     *
     * @param bool $current
     *
     * @return mixed
     */
    public function setCurrent(bool $current = true);

    /**
     * Register observers with the model.
     *
     * @param  object|array|string $classes
     *
     * @return void
     */
    public static function observe($classes);

}
