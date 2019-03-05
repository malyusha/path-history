<?php

return [
    'table'           => 'path_history',

    /*
    |--------------------------------------------------------------------------
    | Path History model
    |--------------------------------------------------------------------------
    |
    | Instance of PathHistoryInterface.
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
    | 'prefixes' => [
    |    [
    |        // Url that starting with "shop" (e.g. "/shop/{category_slug}",
    |        // "/shop/{category_slug}/{subcategory_slug}/{entity_slug}")
    |        'path'  => 'shop',
    |        // Types can be string (morph type) of class name. If it
    |        // represents morph type of class you should check if morph map is
    |        // set correctly.
    |        'types' => [
    |            'products',
    |            'product_categories',
    |        ],
    |    ],
    |   ],
    | `path` - Url that starting with "shop" (e.g. "/shop/{category_slug}",
    | "/shop/{category_slug}/{subcategory_slug}/{entity_slug}")
    | `types` - Types can be string (morph type) of class name. If it represents morph type of class you should check
    | if morph map is set correctly.
    */
    'prefixes'        => [],

    /*
    |--------------------------------------------------------------------------
    | Controllers for paths
    |--------------------------------------------------------------------------
    |
    | This property represents map of type -> controller, responsible for
    | resolving which controller should be called on specific path.
    | Imagine user comes to our "/shop/{...path}" path. How will we process this
    | specific path and which controller method should we call to show content
    | for this path? This property makes us aware of this case.
    | Example:
    | 'controllers' => [
    |     Model::class => Controller::class,
    | ]
    */
    'controllers'     => [],
];
