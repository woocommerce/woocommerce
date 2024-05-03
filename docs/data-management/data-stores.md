---
post_title: How to manage WooCommerce Data Stores
menu_title: Manage data stores
Tags: how-to
---

## Introduction

Data store classes act as a bridge between WooCommerce's data CRUD classes (`WC_Product`, `WC_Order`, `WC_Customer`, etc) and the database layer. With the database logic separate from data, WooCommerce becomes more flexible. The data stores shipped with WooCommerce core (powered by WordPress' custom posts system and some custom tables) can be swapped out for a different database structure, type, or even be powered by an external API.

This guide will walk through the structure of a data store class, how to create a new data store, how to replace a core data store, and how to call a data store from your own code.

The examples in this guide will look at the [`WC_Coupon`](https://github.com/woocommerce/woocommerce/blob/dcecf0f22890f3cd92fbea13a98c11b2537df2a8/includes/class-wc-coupon.php#L19) CRUD data class and [`WC_Coupon_Data_Store_CPT`](https://github.com/woocommerce/woocommerce/blob/dcecf0f22890f3cd92fbea13a98c11b2537df2a8/includes/data-stores/class-wc-coupon-data-store-cpt.php), an implementation of a coupon data store using WordPress custom post types. This is how coupons are currently stored in WooCommerce.

The important thing to know about `WC_Coupon` or any other CRUD data class when working with data stores is which props (properties) they contain. This is defined in the [`data`](https://github.com/woocommerce/woocommerce/blob/dcecf0f22890f3cd92fbea13a98c11b2537df2a8/includes/class-wc-coupon.php#L26) array of each class.

## Structure

Every data store for a CRUD object should implement the `WC_Object_Data_Store_Interface` interface.

`WC_Object_Data_Store_Interface` includes the following methods:

* `create`
* `read`
* `update`
* `delete`
* `read_meta`
* `delete_meta`
* `add_meta`
* `update_meta`

The `create`, `read`, `update`, and `delete` methods should handle the CRUD logic for your props:

* `create` should create a new entry in the database. Example: Create a coupon.
* `read` should query a single entry from the database and set properties based on the response. Example: Read a coupon.
* `update` should make changes to an existing entry. Example: Update or edit a coupon.
* `delete` should remove an entry from the database. Example: Delete a coupon.

All data stores must implement handling for these methods.

In addition to handling your props, other custom data can be passed. This is considered `meta`. For example, coupons can have custom data provided by plugins.

The `read_meta`, `delete_meta`, `add_meta`, and `update_meta` methods should be defined so meta can be read and managed from the correct source. In the case of our WooCommerce core classes, we define them in `WC_Data_Store_WP` and then use the same code for all of our data stores. They all use the WordPress meta system. You can redefine these if meta should come from a different source.

Your data store can also implement other methods to replace direct queries. For example, the coupons data store has a public `get_usage_by_user_id` method. Data stores should always define and implement an interface for the methods they expect, so other developers know what methods they need to write. Put another way, in addition to the `WC_Object_Data_Store_Interface` interface, `WC_Coupon_Data_Store_CPT` also implements `WC_Coupon_Data_Store_Interface`.

## Replacing a data store

Let's look at how we would replace the `WC_Coupon_Data_Store_CPT` class with a `WC_Coupon_Data_Store_Custom_Table` class. Our examples will just provide stub functions, instead of a full working solution. Imagine that we would like to store coupons in a table named `wc_coupons` with the following columns:

```text
id, code, amount, date_created, date_modified, discount_type, description, date_expires, usage_count,individual_use, product_ids, excluded_product_ids, usage_limit, usage_limit_per_user, limit_usage_to_x_items, free_shipping, product_categories, excluded_product_categories, exclude_sale_items, minimum_amount, maximum_amount, email_restrictions, used_by
```

These column names match 1 to 1 with prop names.

First we would need to create a new data store class to contain our logic:

```php
/**
 * WC Coupon Data Store: Custom Table.
 */
class WC_Coupon_Data_Store_Custom_Table extends WC_Data_Store_WP implements WC_Coupon_Data_Store_Interface, WC_Object_Data_Store_Interface {

}
```

Note that we implement the main `WC_Object_Data_Store_Interface` interface as well as the ` WC_Coupon_Data_Store_Interface` interface. Together, these represent all the methods we need to provide logic for.

We would then define the CRUD handling for these properties:

```php
/**
 * Method to create a new coupon in the database.
 *
 * @param WC_Coupon
 */
public function create( &$coupon ) {
	$coupon->set_date_created( current_time( 'timestamp' ) );
	
	/**
	 * This is where code for inserting a new coupon would go.
	 * A query would be built using getters: $coupon->get_code(), $coupon->get_description(), etc.
	 * After the INSERT operation, we want to pass the new ID to the coupon object.
	 */
	$coupon->set_id( $coupon_id );
	
	// After creating or updating an entry, we need to also cause our 'meta' to save.
	$coupon->save_meta_data();
	
	// Apply changes let's the object know that the current object reflects the database and no "changes" exist between the two.
	$coupon->apply_changes();
	
	// It is helpful to provide the same hooks when an action is completed, so that plugins can interact with your data store.
	do_action( 'woocommerce_new_coupon', $coupon_id );
}

/**
 * Method to read a coupon.
 *
 * @param WC_Coupon
 */
public function read( &$coupon ) {
	$coupon->set_defaults();

	// Read should do a check to see if this is a valid coupon
	// and otherwise	throw an 'Invalid coupon.' exception.
	// For valid coupons, set $data to contain our database result.
	// All props should be set using set_props with output from the database. This "hydates" the CRUD data object.
	$coupon_id = $coupon->get_id();
	$coupon->set_props( array(
		'code'                        => $data->code,
		'description'                 => $data->description,
		// ..
	) );
	
	
	// We also need to read our meta data into the object.
	$coupon->read_meta_data();
	
	// This flag reports if an object has been hydrated or not. If this ends up false, the database hasn't correctly set the object.
	$coupon->set_object_read( true );
	do_action( 'woocommerce_coupon_loaded', $coupon );
}

/**
 * Updates a coupon in the database.
 *
 * @param WC_Coupon
 */
public function update( &$coupon ) {
	// Update coupon query, using the getters.
	
	$coupon->save_meta_data();
	$coupon->apply_changes();
	do_action( 'woocommerce_update_coupon', $coupon->get_id() );
}

/**
 * Deletes a coupon from the database.
 *
 * @param WC_Coupon
 * @param array $args Array of args to pass to the delete method.
 */
public function delete( &$coupon, $args = array() ) {
	// A lot of objects in WordPress and WooCommerce support
	// the concept of trashing. This usually is a flag to move the object
	// to a "recycling bin". Since coupons support trashing, your layer should too.
	// If an actual delete occurs, set the coupon ID to 0.
	
	$args = wp_parse_args( $args, array(
		'force_delete' => false,
	) );

	$id = $coupon->get_id();

	if ( $args['force_delete'] ) {
		// Delete Query
		$coupon->set_id( 0 );
		do_action( 'woocommerce_delete_coupon', $id );
	} else {
		// Trash Query
		do_action( 'woocommerce_trash_coupon', $id );
	}
}
```

We are extending `WC_Data_Store_WP` so our classes will continue to use WordPress' meta system.

As mentioned in the structure section, we are responsible for implementing the methods defined by `WC_Coupon_Data_Store_Interface`. Each interface describes the methods and parameters it accepts, and what your function should do.

A coupons replacement would look like the following:

```php
/**
 * Increase usage count for current coupon.
 * 
 * @param WC_Coupon
 * @param string $used_by Either user ID or billing email
 */
public function increase_usage_count( &$coupon, $used_by = '' ) {

}

/**
 * Decrease usage count for current coupon.
 * 
 * @param WC_Coupon
 * @param string $used_by Either user ID or billing email
 */
public function decrease_usage_count( &$coupon, $used_by = '' ) {

}

/**
 * Get the number of uses for a coupon by user ID.
 * 
 * @param WC_Coupon
 * @param id $user_id
 * @return int
 */
public function get_usage_by_user_id( &$coupon, $user_id ) {

}

/**
 * Return a coupon code for a specific ID.
 * @param int $id
 * @return string Coupon Code
 */
 public function get_code_by_id( $id ) {
 
 }

 /**
  * Return an array of IDs for for a specific coupon code.
  * Can return multiple to check for existence.
  * @param string $code
  * @return array Array of IDs.
  */
 public function get_ids_by_code( $code ) {
 
 }
```

Once all the data store methods are defined and logic written, we need to tell WooCommerce to load our new class instead of the built-in class. This is done using the `woocommerce_data_stores` filter. An array of data store slugs is mapped to default WooCommerce classes. Example:

```php
'coupon'              => 'WC_Coupon_Data_Store_CPT',
'customer'            => 'WC_Customer_Data_Store',
'customer-download'   => 'WC_Customer_Download_Data_Store',
'customer-session'    => 'WC_Customer_Data_Store_Session',
'order'               => 'WC_Order_Data_Store_CPT',
'order-refund'        => 'WC_Order_Refund_Data_Store_CPT',
'order-item'          => 'WC_Order_Item_Data_Store',
'order-item-coupon'   => 'WC_Order_Item_Coupon_Data_Store',
'order-item-fee'      => 'WC_Order_Item_Fee_Data_Store',
'order-item-product'  => 'WC_Order_Item_Product_Data_Store',
'order-item-shipping' => 'WC_Order_Item_Shipping_Data_Store',
'order-item-tax'      => 'WC_Order_Item_Tax_Data_Store',
'payment-token'       => 'WC_Payment_Token_Data_Store',
'product'             => 'WC_Product_Data_Store_CPT',
'product-grouped'     => 'WC_Product_Grouped_Data_Store_CPT',
'product-variable'    => 'WC_Product_Variable_Data_Store_CPT',
'product-variation'   => 'WC_Product_Variation_Data_Store_CPT',
'shipping-zone'       => 'WC_Shipping_Zone_Data_Store',
```

We specifically want to target the coupon data store, so we would do something like this:

```php
function myplugin_set_wc_coupon_data_store( $stores ) {
	$stores['coupon'] = 'WC_Coupon_Data_Store_Custom_Table';
	return $stores;
}

add_filter( 'woocommerce_data_stores', 'myplugin_set_wc_coupon_data_store' );
```

Our class would then be loaded by WooCommerce core, instead of `WC_Coupon_Data_Store_CPT`.

## Creating a new data store

### Defining a new product type

Does your extension create a new product type? Each product type has a data store in addition to a parent product data store. The parent store handles shared properties like name or description and the child handles more specific data.

For example, the external product data store handles "button text" and "external URL". The variable data store handles the relationship between parent products and their variations.

Check out [this walkthrough](https://developer.woo.com/2017/02/06/wc-2-7-extension-compatibility-examples-3-bookings/) for more information on this process.

### Data store for custom data

If your extension introduces a new database table, new custom post type, or some new form of data not related to products, orders, etc, then you should implement your own data store.

Your data store should still implement `WC_Object_Data_Store_Interface` and provide the normal CRUD functions. Your data store should be the main point of entry for interacting with your data, so any other queries or operations should also have methods.

The [shipping zone data store](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/data-stores/class-wc-shipping-zone-data-store.php) serves as a good example for a "simple" data store using a custom table. The coupons code is a good example for a data store using a custom post type.

All you need to do to register your data store is add it to the `woocommerce_data_stores` filter:

```php
function myplugin_set_my_custom_data_store( $stores ) {
	$stores['mycustomdata'] = 'WC_My_Custom_Data_Store';
	return $stores;
}

add_filter( 'woocommerce_data_stores', 'myplugin_set_my_custom_data_store' );
```

You can then load your data store like any other WooCommerce data store.

## Calling a data store

Calling a data store is as simple as using the static `WC_Data_Store::load()` method:

```php
// Load the shipping zone data store.
$data_store = WC_Data_Store::load( 'shipping-zone' );
// Get the number of shipping methods for zone ID 4.
$num_of_methods = $data_store->get_method_count( 4 );
```

You can also chain methods:

```php
// Get the number of shipping methods for zone ID 4.
$num_of_methods = WC_Data_Store::load( 'shipping-zone' )->get_method_count( 4 );
```

The `::load()` method works for any data store registered to `woocommerce_data_stores`, so you could load your custom data store:

```php
$data_store = WC_Data_Store::load( 'mycustomdata' );
```

## Data store limitations and WP Admin

Currently, several WooCommerce screens still rely on WordPress to list objects. Examples of this include coupons and products.

If you replace data via a data store, some parts of the existing UI may fail. An example of this may be lists of coupons when using the `type` filter. This filter uses meta data, and is in turn passed to WordPress which runs a query using the `WP_Query` class. This cannot handle data outside of the regular meta tables (Ref #19937). To get around this, usage of `WP_Query` would need to be deprecated and replaced with custom query classes and functions.
