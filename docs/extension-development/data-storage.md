---
post_title: Data storage primer
menu_title: Data storage
tags: reference
---

When developing for WordPress and WooCommerce, it's important to consider the nature and permanence of your data. This will help you decide the best way to store it. Here's a quick primer:

## Transients
If the data may not always be present (i.e., it expires), use a [transient](https://developer.wordpress.org/apis/handbook/transients/). Transients are a simple and standardized way of storing cached data in the database temporarily by giving it a custom name and a timeframe after which it will expire and be deleted.

## WP Cache
If the data is persistent but not always present, consider using the [WP Cache](https://developer.wordpress.org/reference/classes/wp_object_cache/). The WP Cache functions allow you to cache data that is computationally expensive to regenerate, such as complex query results.

## wp_options Table
If the data is persistent and always present, consider the [wp_options table](https://developer.wordpress.org/apis/handbook/options/). The Options API is a simple and standardized way of storing data in the wp_options table in the WordPress database.

## Post Types
If the data type is an entity with n units, consider a [post type](https://developer.wordpress.org/post_type/). Post types are "types" of content that are stored in the same way, but are easy to distinguish in the code and UI.

## Taxonomies
If the data is a means of sorting/categorizing an entity, consider a [taxonomy](https://developer.wordpress.org/taxonomy/). Taxonomies are a way of grouping things together.

## Logging
Logs should be written to a file using the [WC_Logger](woocommerce.com/wc-apidocs/class-WC_Logger.html) class. This is a simple and standardized way of recording events and errors for debugging purposes.

Remember, the best method of data storage depends on the nature of the data and how it will be used in your application.
