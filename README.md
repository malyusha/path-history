# Path History
Laravel package that allows you to create nested/human-readable paths(urls) for your eloquent models.

## Installation
run `composer require malyusha/path-history` inside project directory.

## Why?
Sometimes you may want to create categorized news/articles/products pages and to see final URL to be like
`http(s)://your-domain.com/{category}/{product}`. So, what are steps to do routes, resolving such patterns?

Create routes for each entity. So for each change in base route parameters you will change it's dependencies.
    * Create something like `Route::get('{category_slug}')->name('products_category')->uses('ProductCategoryController@showCategory')`;
    * Then create route for product `Route::get('{category_slug}/{product_identifier}')->name('product')->uses('ProductsController@showProduct')`;
    
But what if you have nested categories for each entity? Ok, we can handle it, just add `where` condition with pattern on routes:
`->where('category_slug', '[\w\-/_]+')`. So, now your router will know that `category_slug` can contain slashes, but
how can we determine where `category_slug` ends and where `product_identifier` starts? That's it, we need to prefix
our product/article route to tell resolver how it works.
Modifying last route to `Route::get('{category_slug}/p-{product_identifier}')->name('product')->uses('ProductsController@showProduct')`
will give us what we wanted. What are the main problems of this method?

1. Each entity must have it's own route;
2. Can't redirect from old URL to new automatically. Imagine having 2 categories (`black`, `white`) with 20 subcategories each.
Each subcategory has ~20-40 products/articles. Now, you decided to change category `black` to `black-color` and you'll
have to add redirects from (20 * ~20-40) URLs to new ones.
3. Prefix for entity with 2-nd level and upper. Not critical but still;

** This package solves problem with that kind of routes and model's URLs **

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
3. For detailed explanation of configuration parameters check out **configuration** section;

## Set up model URL generation

1. Include `\Malyusha\PathHistory\HasPathHistory` trait in your model;
```
    namespace App\Entities;
    
    use App\DescendantRetrievers\Shop\NestedSetDescendants;
    use App\DescendantRetrievers\Shop\ProductsOfCategory;
    
    class ProductCategory extends Model
    {
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
    }
```

As you can see, we define category model that uses out trait. That trait will setup model event listeners to update 
all paths of current and nested categories and their products.
You may have noticed that we've defined additional properties of our model, let's look at them more detailed:

| Property | Type | |Description |
|---|---|---|
| `updatePathOnChangeAttributes`   | `Array` | Array of additional model's attributes that should be watched for change to generate new URL for model.
| `parentPathRelation` | String | Parent model (which path must be prefix for current model) relation name. Required if `shouldUseParentPaths` method returns true  |
| `descendantRetrievers` | Array | Array of classes that implement interface `DescendantRetrieverContract`. This array is used to load descendants and update their links. |
| `shouldUseParentPaths` | Function | Determines whether model has parents |
2. 
