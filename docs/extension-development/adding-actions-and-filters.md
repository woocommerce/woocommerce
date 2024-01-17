---
post_title: How to add actions and filters
menu_title: Add actions and filters
tags: how-to
---

Like many WordPress plugins, WooCommerce provides a range of actions and filters through which developers can extend and modify the platform.

Often, when writing new code or revising existing code, there is a desire to add new hooks-but this should always be done with thoughtfulness and care. This document aims to provide high-level guidance on the matter.

Practices we generally allow, support and encourage include:

* [Using existing hooks (or other alternatives) in preference to adding new hooks](#prefer-existing-hooks-or-other-alternatives)
* [Adding lifecycle hooks](#adding-lifecycle-hooks)
* [Optional escape hooks](#escape-hooks)
* [Modifying the inputs and outputs of global rendering functions](#modifying-function-input-and-output-global-rendering-functions)
* [Preferring the passing of objects over IDs](#prefer-passing-objects-over-ids)

On the flip side, there are several practices we discourage:

* [Tying lifecycle hooks to methods of execution](#tying-lifecycle-hooks-to-methods-of-execution)
* [Using filters as feature flags](#using-filters-as-feature-flags)
* [Placing filter hooks inside templates and data stores](#placement-of-filter-hooks)
* [Enumeration values within hook names](#enumeration-values-inside-hook-names)

Beyond those items, we generally otherwise adhere to WordPress coding standards. In regards to hooks, that specifically means following the:

* [Documentation standards for hooks](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/#4-hooks-actions-and-filters)
* [Guidance on Dynamic hook names](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/#interpolation-for-naming-dynamic-hooks)

Please note that we provide example code throughout this guide to help illustrate some of the principles. However, to keep things concise, we usually omit unnecessary detail, including doc blocks (in practice, though, hooks should always be accompanied by doc blocks!).

### Prefer existing hooks (or other alternatives)

Hooks come with a long-term obligation: the last thing we want is to add a new hook that developers come to depend on, only to strip it away again. However, this can lead to difficulties when the time comes to refactor a piece of code that contains hooks, sometimes delaying meaningful change or limiting how easily we can implement a change without compromising on backward compatibility commitments.

For those reasons, we always prefer that-wherever reasonable-an existing hook or alternative approach in preference to adding a new hook.

### Adding lifecycle hooks

Lifecycle hooks can be used to communicate that a lifecycle event is about to start, or that it has concluded. Examples of such events include:

* Main product loop
* Dispatching emails
* Rendering a template
* Product or order status changes

In general, lifecycle hooks:

* Come in pairs ('before' and 'after')
* Are always actions, never filters
* The 'before' hook will generally always provide callbacks with the arguments array, if there is one
* The 'after' hook will generally also provide callbacks with the function's return value, if there is one

Note that lifecycle hooks primarily exist to let other systems observe, rather than to modify the result. Of course, this does not stop the function author from additionally providing a filter hook that serves this function.

For example, noting that it is the process of fetching the promotions which we view as the "lifecycle event", and not the function itself:

```php
function woocommerce_get_current_promotions( ...$args ) {
    /* Any initial prep, then first lifecycle hook... */
    do_action( 'woocommerce_before_get_current_promotions', $args );
    /* ...Do actual work, then final lifecycle hook... */
    do_action( 'woocommerce_after_get_current_promotions', $result, $args );
    /* ...Return the result, optionally via a filter... */
    return apply_filters( 'woocommerce_get_current_promotions', $result, $args );
}
```

### Escape hooks

In some cases, it may be appropriate to support short-circuiting of functions or methods. This is what we call an escape hook, and can be useful as a means of overriding code when a better way of doing so is not available.

* Escape hooks are always filters
* They should always supply null as the initial filterable value
* If the value is changed to a non-null value, then the function should exit early by returning that new value

For type safety, care should be taken to ensure that, if a function is short-circuited, the return type matches the function signature and/or return type stated in the function doc block.

Example:

```php
function get_product_metrics( $args ): array {
    $pre = apply_filters( 'pre_woocommerce_get_product_metrics', null, $args );

    if ( $pre !== null ) {
        return (array) $pre;
    }

    /* ...Default logic... */
    return $metrics;
}
```

### Modifying function input and output (global rendering functions)

In the case of global rendering or formatting functions (so-called "template tags"), where it is not readily possible to implement better alternatives, it is permissible to add filters for both the function arguments and the function's return value.

This should be done sparingly, and only where necessary. Remember that while providing opportunities for other components to perform extensive customization, it can potentially derail other components which expect unmodified output.

Example:

```php
function woocommerce_format_sale_price( ...$args ): string {
    /* Prep to fill in any missing $args values... */
    $args = (array) apply_filters( 'woocommerce_format_sale_price_args', $args );
    /* ...Actual work to determine the $price string... */
    return (string) apply_filters( 'woocommerce_format_sale_price', $price, $args );
}
```

### Prefer passing objects over IDs

Some actions or filters provide an object ID (such as a product ID) as their primary value, while others will provide the actual object itself (such as a product object). For consistency, it is preferred that objects be passed.

Example:

```php
function get_featured_product_for_current_customer( ) {
    /* ...Logic to find the featured product for this customer... */

    return apply_filters( 
        'woocommerce_featured_product_for_current_customer', 
        $product, /* WC_Product */
        $customer 
    );
}
```

### Tying lifecycle hooks to methods of execution

There can sometimes be multiple paths leading to the same action. For instance, an order can be updated via the REST API, through the admin environment, or on the front end. It may additionally happen via ajax, or via a regular request.

It is important however not to tie hooks for high-level processes to specific execution paths. For example, an action that fires when an order is created must not only be fired when this happens in the admin environment via an ajax request.

Instead, prefer a more generic hook that passes context about the method of execution to the callback.

Example of what we wish to avoid:

```php
/**
 * Pretend this function is only called following an ajax request
 * (perhaps it is itself hooked in using a `wp_ajax_*` action).
 */
function on_ajax_order_creation() {
    /* Avoid this! */
    do_action( 'woocommerce_on_order_creation' );
}
```

### Using filters as feature flags

It is sometimes tempting to use a filter as a sort of feature flag, that enables or disables a piece of functionality. This should be avoided! Prefer using an option:

* Options persist in the database.
* Options are already filterable (ideal for a temporary override).

Example of what we wish to avoid:

```php
/* Avoid this */
$super_products_enabled = (bool) apply_filters( 'woocommerce_super_products_are_enabled', true );

/* Prefer this */
$super_products_enabled = get_option( 'woocommerce_super_products_are_enabled', 'no' ) === 'yes';
```

### Placement of filter hooks

Filters should not be placed inside templates-only actions. If it is important that a value used within a template be filterable, then the relevant logic should be moved to whichever function or method decides to load the template-the result being passed in as a template variable.

It is also preferred that filter hooks not be placed inside data-store classes, as this can reduce the integrity of those components: since, by design, they are replaceable by custom implementations-the risk of accidentally breaking those custom stores is higher.

### Enumeration values inside hook names

Though there is a case for dynamic hook names (where part of the hook name is created using a variable), a good rule of thumb is to avoid this if the variable contains what might be considered an enumeration value.

This might for instance include a case where an error code forms part of the hook name.

Example (of what we wish to avoid):

```php
if ( is_wp_error( $result ) ) {
    /* Avoid this */
    $error_code = $result->get_error_code();
    do_action( "woocommerce_foo_bar_{$error_code}_problem", $intermediate_result );
    
    /* Prefer this */
    do_action( 'woocommerce_foo_bar_problem', $result );
}
```

The primary reason for avoiding this is that the more values there are in the enumeration set, the more filters developers have to include in their code.

### Summary

This document is a high-level guide to the inclusion and placement of hooks, not an exhaustive list. There will occasionally be exceptions, and there may be good rules and methodologies we are missing: if you have suggestions or ideas for improvement, please reach out!
