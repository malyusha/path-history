<?php

return [
    /*
    |-------------------------------------------------------------------------
    | Path History table
    |-------------------------------------------------------------------------
    |
    | Table where all urls for all models that are using trait will be stored.
    |
    */
    'table'           => 'path_history',

    /*
    |--------------------------------------------------------------------------
    | Path History model
    |--------------------------------------------------------------------------
    |
    | Instance of PathHistoryContract.
    |
    */
    'model'           => \Malyusha\PathHistory\Models\PathHistory::class,

    /*
    |--------------------------------------------------------------------------
    | Redirect status
    |--------------------------------------------------------------------------
    |
    | Redirect status for paths, that are not current anymore.
    | Default is 302, but you can change it to 301, for example, when you are
    | sure, that you'll never restore previous URL of entity.
    | Note: it works only if you add redirect middleware in Http\Kernel list.
    |
    */
    'redirect_status' => 302,

    /*
    |--------------------------------------------------------------------------
    | Path prefixes
    |--------------------------------------------------------------------------
    |
    | Here you may set up prefixes for site sections. For instance, if you want
    | to  show some sections of your site not under root path (e.g. "/"), you can
    | set up which prefixes are responsible for which sections of site.
    | Example:
    | 'paths' => [
    |    [
    |        // Url that starting with "shop" (e.g. "/shop/{category_slug}",
    |        // "/shop/{category_slug}/{subcategory_slug}/{entity_slug}")
    |        'prefix'  => 'shop',
    |        // Types can be string (morph type) of class name. If it
    |        // represents morph type of class you should check if morph map is
    |        // set correctly.
    |        'types' => [
    |            'products',
    |            'product_categories',
    |        ],
    |         // Setup global controller responsible for resolving this sections with given types
    |         'controller' => 'App\Http\Controllers\ProductsSectionController',
    |    ],
    |   ],
    | `path` - Url that starting with "shop" (e.g. "/shop/{category_slug}",
    | "/shop/{category_slug}/{subcategory_slug}/{entity_slug}")
    | `types` - Types can be string (morph type) of class name. If it represents morph type of class you should check
    | if morph map is set correctly.
    */
    'paths'           => [],
];
