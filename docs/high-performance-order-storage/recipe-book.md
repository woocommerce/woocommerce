---
post_title: HPOS extension recipe book
tags: how-to
---
## What is High-Performance Order Storage (HPOS)?

Up until recently, WooCommerce stored order-related data in the post and postmeta tables in the database as a custom WordPress post type, which allowed everyone working in the ecosystem to take advantage of extensive APIs provided by the WordPress core in managing orders as custom post types.

However, in early 2022, [we announced the plans to migrate to dedicated tables for orders](https://developer.woocommerce.com/2022/01/17/the-plan-for-the-woocommerce-custom-order-table/). Orders in their own tables will allow the shops to scale more easily, make the data storage simpler and increase reliability. For further details, please check out our [deep dive on the database structure on our dev blog](https://developer.woocommerce.com/2022/09/15/high-performance-order-storage-database-schema/).

Generally, WooCommerce has tried to be fully backward compatible with the older versions, but, as a result of this project, extension developers will be required to make some changes to their plugins to take advantage of the HPOS. This is because the underlying data structure has changed fundamentally.

More specifically, instead of using WordPress-provided APIs to access order data, developers will need to use WooCommerce-specific APIs. We [introduced these APIs in WooCommerce version 3.0](https://developer.woocommerce.com/2017/04/04/say-hello-to-woocommerce-3-0-bionic-butterfly/) to make the transition to HPOS easier.

In this guide, we will focus on the changes required to make an extension, or any snippet of custom code, compatible with HPOS.

For details on how to take enable or disable HPOS, as well as details on how orders are synced between datastores please refer to the [HPOS documentation](https://woocommerce.com/document/high-performance-order-storage/).

## Backward compatibility

To make the transition easier for shops and developers alike, we have tried to be as backward compatible as possible. One of the major compatibility issues with this project is that since the underlying data structure was `wp_posts` and `wp_postmeta` tables, circumventing the WC-specific CRUD classes and accessing the data directly using WordPress APIs worked fine.

Now that this has changed, directly reading from these WordPress tables may mean reading an outdated order, and directly writing to these tables may mean updating an order that will not be read. Since this is a pretty significant issue, we have added a few mitigations for the transitory period.

### Switching data source

Additionally, you can switch from using HPOS to posts tables manually if you see an issue with the new tables. To switch back, change the "Order data storage" setting in the WC > Settings > Advanced > Features.

## Supporting High-Performance Order Storage in your extension

While the backward compatibility policies make it easier for merchants to use the project, for extension developers this means that you have to support both Posts and HPOS for a while.

To help with this, we have provided a few guidelines for extension developers to follow.

**Note:** We recommend you use the development version of WooCommerce while working on your extension, in order to get all of the latest HPOS fixes and APIs. Refer to our [development guide](https://github.com/woocommerce/woocommerce/blob/trunk/DEVELOPMENT.md) to understand how the WooCommerce repo is structured and how to build the plugin from source.

### Detecting whether HPOS tables are being used in the store

While the WooCommerce CRUD API will let you support both posts and custom tables without additional effort most of the time, in some cases (like when you are writing a SQL query for better performance) you would want to know whether the store is using HPOS tables or not. In this case, you can use the following pattern:

```php
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
	// HPOS usage is enabled.
} else {
	// Traditional CPT-based orders are in use.
}
```

### Auditing the code base for direct DB access usage

To support HPOS tables, a good place to start is to audit your code base for direct DB access and usage of WordPress APIs that shouldn't be used to work with orders anymore. You can search using the following regexp to perform this audit:

```regexp
wpdb|get_post|get_post_field|get_post_status|get_post_type|get_post_type_object|get_posts|metadata_exists|get_post_meta|get_metadata|get_metadata_raw|get_metadata_default|get_metadata_by_mid|wp_insert_post|add_metadata|add_post_meta|wp_update_post|update_post_meta|update_metadata|update_metadata_by_mid|delete_metadata|delete_post_meta|delete_metadata_by_mid|delete_post_meta_by_key|wp_delete_post|wp_trash_post|wp_untrash_post|wp_transition_post_status|clean_post_cache|update_post_caches|update_postmeta_cache|post_exists|wp_count_post|shop_order
```

Please note that you will find lots of false positives, but this regular expression is quite thorough and should catch most of the true positives.

Search for the above regular expression in your source code, and:

1. Go through the matches one by one and check whether the occurrence relates to an order. Most of the matches will probably be false positives i.e. they won't be related to orders.
2. If you see one of the matches are directly accessing or modifying order data, you will need to change it to use WooCommerce's CRUD API instead.

### APIs for getting/setting posts and postmeta

Any code getting posts directly can be converted to a `wc_get_order` call instead:

```php
// Instead of
$post = get_post( $post_id ); // returns WP_Post object.
// use
$order = wc_get_order( $post_id ); // returns WC_Order object.
```

For interacting with metadata, use the `update_`/`add_`/`delete_metadata` methods on the order object, followed by a `save` call. WooCommerce will take care of figuring out which tables are active, and saving data in appropriate locations.

```php
// Instead of following update/add/delete methods, use:
update_post_meta( $post_id, $meta_key_1, $meta_value_1 );
add_post_meta( $post_id, $meta_key_2, $meta_value_2 );
delete_post_meta( $post_id, $meta_key_3, $meta_value_3 );

// use
$order = wc_get_order( $post_id );
$order->update_meta_data( $meta_key_1, $meta_value_1 );
$order->add_meta_data( $meta_key_2, $meta_value_2 );
$order->delete_meta_data( $meta_key_3, $meta_value_3 );
$order->save();
```

💡 Calling the `save()` method is a relatively expensive operation, so you may wish to avoid calling it more times than necessary (for example, if you know it will be called later in the same flow, you may wish to avoid additional earlier calls when operating on the same object).

When getting exact type of an order, or checking if given ID is an order, you can use methods from `OrderUtil` class.

```php
// Pattern to check when an ID is an order
'shop_order' === get_post_type( $post_id ); // or
in_array( get_post_type( $post_type ), wc_get_order_types() );

// replace with:
use Automattic\WooCommerce\Utilities\OrderUtil;
'shop_order' === OrderUtil::get_order_type( $post_id ); // or
OrderUtil::is_order( $post_id, wc_get_order_types() );
```

### Audit for order administration screen functions

As WC can't use the WordPress-provided post list and post edit screens, we have also added new screens for order administration. These screens are very similar to the one you see in the WooCommerce admin currently (except for the fact that they are using HPOS tables). You can use the following regular expression to perform this audit:

```regexp
post_updated_messages|do_meta_boxes|enter_title_here|edit_form_before_permalink|edit_form_after_title|edit_form_after_editor|submitpage_box|submitpost_box|edit_form_advanced|dbx_post_sidebar|manage_shop_order_posts_columns|manage_shop_order_posts_custom_column
```

You will see a lot of false positives here as well. However, if you do encounter a usage where these methods are called for the order screen then to upgrade them to HPOS, the following changes have to be done:

Instead of a `$post` object of the `WP_Post` class, you will need to use an `$order` object of the `WC_Order` class. If it’s a filter or an action, then we will implement a similar filter in the new WooCommerce screen as well and instead of passing the post object, it will accept a WC_Order object instead.

The following snippet shows a way to add meta boxes to the legacy order editor screen when legacy orders are in effect, and to the new HPOS-powered editor screen otherwise:

```php
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

add_action( 'add_meta_boxes', 'add_xyz_metabox' );

function add_xyz_metabox() {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

	add_meta_box(
		'xyz',
		'Custom Meta Box',
		'render_xyz_metabox',
		$screen,
		'side',
		'high'
	);
}
```

The above will also change the parameter passed to the metabox to order. So in your metaboxes, you would have to account for both a post or order object that may be passed. We recommend fetching the order object and working with it completely instead of the passed parameter.

```php
function render_xyz_metabox( $post_or_order_object ) {
    $order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

    // ... rest of the code. $post_or_order_object should not be used directly below this point.
}
```

### Declaring extension (in)compatibility

Once you examined the extension's code, you can declare whether it's compatible with HPOS or not. We've prepared an API to make this easy. To **declare your extension compatible**, place the following code into your **main plugin file**:

```php
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
```

If you know your code doesn't support HPOS, you should declare **incompatibility** in the following way. Place the following code into your **main plugin file**:

```php
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, false );
	}
} );
```

If you prefer to include the compatibility declaration outside of your main plugin file, please pass 'my-plugin-slug/my-plugin.php' instead of the `__FILE__` parameter in the snippets above.

To prevent problems, WooCommerce will warn users if they try to enable HPOS while any of the incompatible plugins are active. It will also display a warning in the Plugins screen to make sure people would know if extension is incompatible.
As many WordPress extensions aren't WooCommerce related, WC will only display this information for extensions that declare `WC tested up to` in the header of the main plugin file.

### New order querying APIs

HPOS, through `WC_Order_Query`, introduces new query types that allow for more complex order queries involving dates, metadata and order fields. Head over to [HPOS: new order querying APIs](https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs) for details and examples.
