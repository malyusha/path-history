<p align="center">
    <img src="https://travis-ci.com/malyusha/path-history.svg?branch=master">
</p>
# Path History
Laravel package that allows you to create nested/human-readable paths(urls) for your eloquent models.

## Installation
run `composer require malyusha/path-history` inside project directory.

## Why?
Sometimes you may want to create categorized news/articles/products pages and to see final URL to be like
`http(s)://your-domain.com/{category}/{product}`. So, what are steps to do routes, resolving such patterns?

* Create routes for each entity. So for each change in base route parameters you will change it's dependencies.
* Create route for category(ies):

```php
Route::get('{category_slug}')->name('products_category')->uses('ProductCategoryController@showCategory');
```
* Then create route for product:

```php
Route::get('{category_slug}/{product_identifier}')->name('product')->uses('ProductsController@showProduct');
```
But what if you have nested categories for each entity? Ok, we can handle it, just add `where` condition with pattern on routes:
`->where('category_slug', '[\w\-/_]+')`. So, now your router will know that `category_slug` can contain slashes, but
how can we determine where `category_slug` ends and where `product_identifier` starts? That's it, we need to prefix
our product/article route to tell resolver how it works.

Modifying last route:
```php
Route::get('{category_slug}/p-{product_identifier}')->name('product')->uses('ProductsController@showProduct');
```
will give us what we wanted. 

So what are the **main problems** of this method?

1. Each entity must have it's own route;
2. Can't redirect from old URL to new automatically. Imagine having 2 categories (`black`, `white`) with 20 subcategories each.
Each subcategory has ~20-40 products/articles. Now, you decided to change category `black` to `black-color` and you'll
have to add redirects from (20 * ~20-40) URLs to new ones.
3. Prefix for entity with 2-nd level and upper. Not critical but still;

**This package solves problem with that kind of routes and model's URLs**

## How it works?
1. Package creates table where all URLs from all "slugable" entities will be stored;
2. You add trait to all entities that are "slugable" and determine a little logic for relative entities;
3. You define routes for your system and include package router registrar;
4. Define controller and it's logic for each type of resolved from URL entity;
5. Done!

## Getting started
1. Publish vendors running `vendor:publish --tag=migrations --tag=config`. This will copy configuration file and generate migration file
with new table creation.
2. Run `php artisan migrate` to run migration for generated file;
3. For detailed explanation of configuration parameters check out [**configuration**](#configuration) section;

## Set up model URL generation

1. Include `\Malyusha\PathHistory\HasPathHistory` trait in your model;
```php
<?php

    namespace App\Entities;
    
    use App\DescendantRetrievers\Shop\NestedSetDescendants;
    use App\DescendantRetrievers\Shop\ProductsOfCategory;
    
    class ProductCategory extends \Illuminate\Database\Eloquent\Model
    {
        use \Malyusha\PathHistory\HasPathHistory;
        
        protected $updatePathOnChangeAttributes = ['parent_id'];
        
        protected $parentPathRelation = 'parent';
    
        protected $descendantRetrievers = [
            NestedSetDescendants::class,
            ProductsOfCategory::class,
        ];
        
        public function parent()
        {
            return $this->belongsTo(static::class, 'parent_id');
        }
        
        public function shouldUseParentPaths(): bool
        {
            return ! $this->isRoot();
        }
        
        public function isRoot(): bool
        {
            return $this->parent_id === null;
        }
        
        public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(Product::class);
        }
    }
```

As you can see, we define category model that uses out trait. That trait will setup model event listeners to update 
all paths of current and nested categories and their products.
You may have noticed that we've defined additional properties of our model, let's look at them more detailed:

| Property | Type | Description |
|---|---|---|
| `updatePathOnChangeAttributes`   | `Array` | Array of additional model's attributes that should be watched for change to generate new URL for model.
| `parentPathRelation` | `String` | Parent model (which path must be prefix for current model) relation name. Required if `shouldUseParentPaths` method returns true  |
| `descendantRetrievers` | `Array` | Array of classes that implement interface `DescendantRetrieverContract`. This array is used to load descendants and update their links. See **[basic example](examples)** for detailed info.|
| `shouldUseParentPaths` | `Function:bool` | Determines whether model has parents. Should return `false` if it's root model of doesn't have parents. |

2. Place route inside your routes file:

```php
<?php
// web.php
Route::get('/')->name('index')->uses('HomeController@showIndex');
// ... Other routes definitions ...

// And here comes shop section
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/')->name('index')->uses('ShopController@index');
    // Call dynamic router registration
    app('ph.router')->register();
});
```

3. Add `shop` to `paths` parameter inside configuration file. More detailed see [here](#configuration).

That's it! You've defined model, controller and route responsible for url generation and handling!

## Retrieving URLs for models
As you've set up models to use path history you'll want to retrieve it's generated urls.
Every model, that uses PathHistoryTrait has getter for `path` attribute. It'll check whether relation `currentPath`
was loaded to model and return it's link. So if you want to use full urls you'll need to preload this relation:

`// IndexController.php`
```php
public function showIndex()
{
    $products = App\Entities\Product::with('currentPath')->get();
    
    return view('index_page', ['products' => $products]);
}
```

`// index_page.blade.php`
```blade
@extends('layouts.default')

@section('content')
<h1>Products</h1>

@foreach($products as $product)
    <img src="{{ $product->getImage() }}}">
    <a href="{{ route('shop.resolve', ['path' => $product->path]) }}">{{ $product->name }}</a>
@endforeach
@endsection
```
When path relation for product is loaded it's link will contain full URL to product page.

## Configuration
You can find configuration of package in `path_history.php` config file after publishing vendors inside your project.
This file contains comments and description of each parameter, but here you can find more detailed info about each of them:

`table` - table where all urls for all models that are using trait will be stored.

`model` - instance of `PathHistoryContract`. This is the main model for manage path-history package logic. Redefine
it to your model if you want to customize something.

`redurect_status` - Redirect status for paths, that are not current anymore. Default is 302, but you can change it to 
301, for example, when you are sure, that you'll never restore previous URL of entity. Note: it works only if you add 
redirect middleware in Http\Kernel list.

`paths` - array of paths. These are main setting, showing how your application should manage urls of configured types of models.
To simplify let's look at sample configuration of `paths` parameter:

```php
<?php

return [
    // other params here...
    
    'paths' => [
       [
           'prefix' => 'news',
           'types'  => [
               \App\Entities\Content\NewsCategory::class => \App\Http\Controllers\News\CategoryController::class,
               \App\Entities\Content\News::class         => \App\Http\Controllers\News\NewsController::class,
           ]
       ],
       [
           'prefix' => 'products',
           'types'  => [
               \App\Entities\Shop\Product::class => \Admin\Http\Controllers\Shop\ProductsController::class,
               \App\Entities\Shop\ProductCategory::class => \Admin\Http\Controllers\Shop\CategoryController::class,
           ],
       ],
    ],
];
```
As you can see we defined configuration for 2 sections: `news` and `products`. Let's see each `path` array config more
detailed:

`prefix` - used to show package what is the root prefix used to handle concrete model type and call it's controller
when prefix for model matched;

`types` - here you need to define array of types to handle as array of `[model => controller]`. Also, if you have controller
to handle all types of entities for prefix you can define it as separated parameter in path array - `[controller => your_controller]`
and make `types` parameter indexed array with models as values.

## Automatic redirects
There are 2 options for automatic redirects from old urls from path history:
#### Redirects from old model's url
Add `Malyusha\PathHistory\Middleware\RedirectsFromOld` middleware into your `web` middleware group inside `App\Http\Kernel`. 
This will redirect users from old created urls for models to changed if they are exist.

So, imagine you have code structure, described [here](#set-up-model-url-generation). You want your shop section to be
located visiting `/shop`. You've created category with slug `men`, so your category page will be
rendered on `/shop/men`. Now, you've created nested category with slug `boots`, so users will see men's boots page
on `/shop/men/boots`. You created first product for these categories - "Awesome Black Boots" with code `awesome-bb` or just 
vendor code of boots, doesn't really matter. This product was shown on this page for 2 months and you've decided to change 
code of category to, for example, `high-shoes`, but robots (google bot, yahoo, etc.) have already indexed your page old
url. Usually you can have separated table for redirects from old-to-new URLs and add them manually, but this package can
handle it for you. So, when you change category's slug to something new all descendant's URLs of category (sub categories, products, etc.)
will now be redirected to new URLs;

#### Global redirects for all system urls
Install `spatie/laravel-missing-page-redirector` by running `composer require spatie/laravel-missing-page-redirector`.

This kind of redirects needed when you need to redirect from old pages that haven't ever existed in your project. For instance,
when you're migration from old version of website to new, you need to save all indexed pages and redirect them to new ones.
Documentation in progress...

## [Examples](examples)
