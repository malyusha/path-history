<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory;

class Config
{
    /**
     * Cached version of normalized paths.
     *
     * @var array|null
     */
    protected static $cachedNormalizedMap;

    /**
     * Cached version of prefixes with types.
     *
     * @var array|null
     */
    protected static $cachedTypesMap;

    /**
     * Returns normalized array of path prefixes and models with their controllers.
     * Resulting array will be: [
     *      {prefix} => [
     *          {type} => {controller},
     *          {type} => {controller}
     *      ]
     * ]
     *
     * @return array[]
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public static function getNormalizedPaths(): array
    {
        if (static::$cachedNormalizedMap !== null) {
            return static::$cachedNormalizedMap;
        }

        $config = config('path_history.paths', []);
        static::$cachedNormalizedMap = [];

        foreach ($config as $item) {
            $prefix = $item['prefix'];
            static::$cachedNormalizedMap[$prefix] = [];
            // if array is associative, them controller already mapped to each type inside `types` property
            // and we don't need to do anything. But if it's not, we need to map global `controller` property to each
            // type
            if (! \Illuminate\Support\Arr::isAssoc($item['types'])) {
                if (! array_key_exists('controller', $item)) {
                    throw new \Malyusha\PathHistory\Exceptions\PathHistoryException('`controller` property must be present when path types defined as values in non-associative array');
                }

                $controller = $item['controller'];

                foreach ((array) $item['types'] as $type) {
                    static::$cachedNormalizedMap[$prefix][$type] = $controller;
                }
            } else {
                static::$cachedNormalizedMap[$prefix] = $item['types'];
            }
        }

        return static::$cachedNormalizedMap;
    }

    /**
     * Returns prefixes map for configuration.
     * Resulting array will contain of ['prefix' => 'type'] values.
     *
     * @return array
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public static function getTypesMap(): array
    {
        if (static::$cachedTypesMap !== null) {
            return static::$cachedTypesMap;
        }

        static::$cachedTypesMap = [];

        foreach (static::getNormalizedPaths() as $prefix => $types) {
            foreach ($types as $type => $controller) {
                static::$cachedTypesMap[$type] = $prefix;
            }
        }

        return static::$cachedTypesMap;
    }

    /**
     * Returns all model types available for path history search.
     *
     * @return array
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(static::getTypesMap());
    }
}