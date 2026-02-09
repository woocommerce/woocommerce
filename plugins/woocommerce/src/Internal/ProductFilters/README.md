# ProductFilters

## Decisions

This section contains all the decisions we've made for this service.

Decisions here are never final. But these documents should serve as a record for whenever we wonder why certain decisions were made.

### Using WP_Query arguments to calculate contextual filter data

#### Context

Current filter blocks and former filter widgets only care about the product catalog query or the query that returns all products. For example, even when added to the Hoodies category, the filter blocks still show data for all products, including ones belonging to the T-shirts category.

Before Gutenberg, we have product archive contexts including Catalog, Taxonomy, Attribute, and Search. With blocks, we have StoreAPI and Product Collection. All types of context can be customized by altering the product archive query, passing more parameters, or adding Product Collection backend filters.

WooCommerce has some solutions, but each has its own limitations:

-   `Filterer` and `WC_Query`, which power filter widgets, return contextual filter data but only work with the main query.
-   `ProductCollectionData`, which powers filter blocks, returns contextual data only for StoreAPI requests.

We need a more robust solution to show only relevant filter data under the current context, regardless of complexity or how heavily customized it is. The new solution should be able to work with any context, including main queries, custom queries, and REST requests.

#### Decision

Learning from `WC_Query`, `Filterer`, and `ProductCollectionData`, we create a new service named it `ProducFilters`, which can be used in all contexts. The key difference between the new service and existing solutions is the data we pass to the service: `WP_Query` arguments.

Every loop in WordPress is powered by `WP_Query`. By taking the common contract (query arguments) to recreate the product query being requested we can ensure a safe boundary for filter data calculation. In other words, filter data is always calculated from the set of products being displayed, which eliminates the possibility of data mismatch between filter data and actual products.

#### Consequences

-   **Reliability**: The accuracy of filter data is now ensured. The provider will always return the correct filter data as long as the query arguments are passed properly.
-   **Forward compatibility**: The provider can keep up with future developments like Product Collection backend filters.
-   **Reusability**: `StoreAPI`/`ProductCollectionData` and `WC_Query` filter methods can be refactored to use the new provider.
