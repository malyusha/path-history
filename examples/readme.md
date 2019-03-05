## Examples
Here you can find some examples for ease understanding of how to use package.

### [Basic example](basic)
Here you'll find basic usage of package for news and news categories models. 

In example you'll see NewsItem and NewsCategory models, related to each other as one-to-many.
Each news item has `code` attribute, which will be taken as basis for generating news item url.
Each news category has `slug` attribute, which also will be taken as basis.
So, to have news item's url including category's slug and item's slug we need to set up
descendant retriever for category to update all items related to category with new urls.
Descendant retriever must return collection of models, that also use trait `HasPathHistory`. 
It loads descendants for model to update their paths when change happened on parent.
