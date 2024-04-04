---
post_title: How to add a custom field to simple and variable products
menu_title: Add custom fields to products
tags: how-to
---

In this tutorial you will learn how to create a custom field for a product and show it in your store. Together we will set up the skeleton plugin, and learn about WP naming conventions and WooCommerce hooks. In the end, you will have a functioning plugin for adding a custom field.

The [full plugin code](https://github.com/EdithAllison/woo-product-custom-fields) was written based on WordPress 6.2 and WooCommerce 7.6.0

## Prerequisites

To do this tutorial you will need to have a WordPress install with the WooCommerce plugin activated, and you will need at least one [simple product set up](woocommerce.com/document/managing-products/), or you can [import the WooCommerce sample product range](woocommerce.com/document/importing-woocommerce-sample-data/).

## Setting up the plugin

To get started, let's do the steps to [create a skeleton plugin](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/create-woo-extension).

First, navigate to your wp-content/plugins folder, then run:

```sh
npx @wordpress/create-block -t @woocommerce/create-woo-extension woo-product-fields
```

Then we navigate to our new folder and run the install and build:

```sh
cd woo-product-fields
npm install # Install dependencies
npm run build # Build the javascript
```

WordPress has its own class file naming convention which doesn't work with PSR-4 out of the box. To learn more about Naming Conventions see the [WP Handbook](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions). We will use the standard format of "class-my-classname.php" format, so let's go to the composer.json file and change the autoload to:

```json
"autoload": {
   	 "classmap": ["includes/", "includes/admin/"]
    },
```

After saving run dump-autoload to generate the class map by running in the Terminal:

```sh
composer dump-autoload -o
```

This generates a new vendor/composer/autoload_classmap.php file containing a list of all our classes in the /includes/ and /includes/admin/ folder. We will need to repeat this command when we add, delete or move class files.

## WooCommerce Hooks

Our aim is to create a new custom text field for WooCommerce products to save new stock information for display in the store. To do this, we need to modify the section of the Woo data in the admin area which holds the stock info.

WooCommerce allows us to add our code to these sections through [hooks](https://developer.wordpress.org/plugins/hooks/), which are a standard WordPress method to extend code. In the "Inventory" section we have the following action hooks available to us:

For our Woo extension, we'll be appending our field right at the end with `woocommerce_product_options_inventory_product_data`.

## Creating our class

Let's get started with creating a new class which will hold the code for the field. Add a new file with the name `class-product-fields.php` to the `/includes/admin/` folder. Within the class, we add our namespace, an abort if anyone tries to call the file directly and a \_\_construct method which calls the `hooks()` method:

```php
<?php

namespace WooProductField\Admin;

defined( 'ABSPATH' ) || exit;

class ProductFields {

    public function __construct() {
		$this->hooks();
    }

    private function hooks() {}
}
```

Then in Terminal we run `composer dump-autoload -o` to regenerate the class map. Once that's done, we add the class to our `setup.php` \_\_construct() function like so:

```php
class Setup {
    public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

		new ProductFields();
    }
```

## Adding the custom field

With the class set up and being called, we can create a function to add the custom field. WooCommerce has its own `woocommerce_wp_text_input( $args )` function which we can use here. `$args` is an array which allows us to set the text input data, and we will be using the global $product_object to access stored metadata.

```php
public function add_field() {
	global $product_object;
	?>
	<div class="inventory_new_stock_information options_group show_if_simple show_if_variable">
		<?php woocommerce_wp_text_input(
			array(
				'id'      	=> '_new_stock_information',
				'label'   	=> __( 'New Stock', 'woo_product_field' ),
				'description' => __( 'Information shown in store', 'woo_product_field' ),
				'desc_tip'	=> true,
				'value' => $product_object->get_meta( '_new_stock_information' )
			)
		); ?>
	</div>
	<?php
}
```

Let's take a look at the arguments in the array. The ID will be used as meta_key in the database. The Label and Description are shown in the data section, and by setting desc_tip to true, it will be shown as a hover over the info icon. The last argument value ensures that if a value is already stored, then it will be shown.

For the div class, the class names `show_if_simple` and `show_if_variable` will control when our section is shown. This is linked to JS code which dynamically hides/reveals sections. If for example, we wanted to hide the section from variable products, then we can simply delete `show_if_variable`.

Now that we have our field, we need to save it. For this, we can hook into woocommerce_process_product_meta which takes two arguments, `$post_id` and `$post`:

```php
public function save_field( $post_id, $post ) {
	if ( isset( $_POST['_new_stock_information'] ) ) {
		$product = wc_get_product( intval( $post_id ) );
		$product->update_meta_data( '_new_stock_information', sanitize_text_field( $_POST['_new_stock_information'] ) );
		$product->save_meta_data();
	}
}
```

This function checks if our new field is in the POST array. If yes, we create the product object, update our metadata and save the metadata. The `update_meta_data` function will either update an existing meta field or add a new one. And as we're inserting into the database, we must [sanitize our field value](https://developer.wordpress.org/apis/security/sanitizing/).

And to make it all work, we add the hooks:

```php
private function hooks() {
	add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_field' ) );
	add_action( 'woocommerce_process_product_meta', array( $this, 'save_field' ), 10, 2 );
}
```

Now if we refresh our product screen, we can see our new field.

If we add data and save the product, then the new meta data is inserted into the database.

At this point you have a working extension that saves a custom field for a product as product meta.
Showing the field in the store
If we want to display the new field in our store, then we can do this with the `get_meta()` method of the Woo product class: `$product->get_meta( '\_new_stock_information' )`

Let's get started by creating a new file /includes/class-product.php. You may have noticed that this is outside the `/admin/` folder as this code will run in the front. So when we set up the class, we also adjust the namespace accordingly:

```php
<?php

namespace WooProductField;

defined( 'ABSPATH' ) || exit;

class Product {
    public function __construct() {
		$this->hooks();
    }

    private function hooks() { }
}
```

Again we run `composer dump-autoload -o` to update our class map.

If you took a look at the extension setup you may have noticed that `/admin/setup.php` is only called if we're within WP Admin. So to call our new class we'll add it directly in `/woo-product-field.php`:

```php
public function __construct() {
	if ( is_admin() ) {
		new Setup();
	}
	new WooProductField\Product();
}
```

For adding the field to the front we have several options. We could create a theme template, but if we are working with a WooCommerce-compatible theme and don't need to make any other changes then a quick way is to use hooks. If we look into `/woocommerce/includes/wc-template-hooks.php` we can see all the existing actions for `woocommerce_single_product_summary` which controls the section at the top of the product page:

For our extension, let's add the new stock information after the excerpt by using 21 as the priority:

```php
private function hooks() {
	add_action( 'woocommerce_single_product_summary', array( $this, 'add_stock_info' ), 21 );
}
```

In our function we output the stock information with the [appropriate escape function](https://developer.wordpress.org/apis/security/escaping/), in this case, I'm suggesting to use `esc_html()` to force plain text.

```php
public function add_stock_info() {
	global $product;
	?>
	<p><?php echo esc_html( $product->get_meta( '_new_stock_information' ) ); ?> </p>
	<?php

    }
```

Now if we refresh the product page our stock information will be shown just below the excerpt:

Fantastic! You have completed this tutorial and have a working WooCommerce extension that adds a new custom field and shows it in the store! 🎉I hope it's shown you how easily you can extend WooCommerce through hooks and tailor it to your or your client's shop requirements!

Below is a bonus task if you are interested in variable products. Feel free to come back to this later.

## How to handle variable products?

The above example was done with a simple product. But what if we have variations, for example, a T-Shirt in multiple sizes and we wanted to store different stock information for each variant? WooCommerce lets us do that with the [variable product type](woocommerce.com/document/variable-product/).

A variable product type has variations as its children. To add a custom field to a variation, we can use the `woocommerce_variation_options_inventory` hook, and to save `woocommerce_save_product_variation` so let's update our `hooks()` method with the new action hooks like so:

```php
private function hooks() {
	add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_field' ) );
	add_action( 'woocommerce_process_product_meta', array( $this, 'save_field' ), 10, 2 );

	add_action( 'woocommerce_variation_options_inventory', array( $this, 'add_variation_field' ), 10, 3 );
	add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_field' ), 10, 2 );
}
```

The setup is very similar to simple products, the main difference is that we need to use the $loop id which distinguishes between the variations, and we will be using the `wrapper_class` to show it as a full width text input:

```php
public function add_variation_field( $loop, $variation_data, $variation ) {
	$variation_product = wc_get_product( $variation->ID );

	woocommerce_wp_text_input(
		array(
			'id' => '\_new_stock_information' . '[' . $loop . ']',
			'label' => \_\_( 'New Stock Information', 'woo_product_field' ),
			'wrapper_class' => 'form-row form-row-full',
			'value' => $variation_product->get_meta( '\_new_stock_information' )
		)
	);
}
```

For saving we use:

```php
public function save_variation_field( $variation_id, $i  ) {
	if ( isset( $_POST['_new_stock_information'][$i] ) ) {
		$variation_product = wc_get_product( $variation_id );
		$variation_product->update_meta_data( '_new_stock_information', sanitize_text_field( $_POST['_new_stock_information'][$i] ) );
		$variation_product->save_meta_data();
	}
}
```

And we now have a new variation field that stores our new stock information. If you cannot see the new field, please make sure to enable "Manage Stock" for the variation by ticking the checkbox in the variation details.

Displaying the variation in the front store works a bit differently for variable products as only some content on the page is updated when the customer makes a selection. This exceeds the scope of this tutorial, but if you are interested have a look at `/woocommerce/assets/js/frontend/add-to-cart-variation.js` to see how WooCommerce does it.

## How to find hooks?

Everyone will have their own preferred way, but for me, the quickest way is to look in the WooCommere plugin code. The code for each data section can be found in `/woocommerce/includes/admin/meta-boxes/views`. To view how the inventory section is handled check the `html-product-data-inventory.php` file, and for variations take a look at `html-variation-admin.php`.
