---
post_title: Shipping method API
menu_title: Shipping method API
tags: reference
---

WooCommerce has a shipping method API which plugins can use to add their own rates. This article will take you through the steps to creating a new shipping method and interacting with the API.

## Create a plugin

First off, create a regular WordPress/WooCommerce plugin - see our [Building Your First Extension guide](../extension-development/building-your-first-extension.md) to get started. You'll define your shipping method class in this plugin file and maintain it outside of WooCommerce.

## Create a function to house your class

Create a function to house your class

To ensure the classes you need to extend exist, you should wrap your class in a function which is called after all plugins are loaded:

```php
function your_shipping_method_init() {
    // Your class will go here
}

add_action( 'woocommerce_shipping_init', 'your_shipping_method_init' );
```

## Create your class

Create your class and place it inside the function you just created. Make sure it extends the shipping method class so that you have access to the API. You'll see below we also init our shipping method options.

```php
if ( ! class_exists( 'WC_Your_Shipping_Method' ) ) {
    class WC_Your_Shipping_Method extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = 'your_shipping_method';
            $this->title       = __( 'Your Shipping Method' );
            $this->method_description = __( 'Description of your shipping method' ); // 
            $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
            $this->init();
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * calculate_shipping function.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package ) {
            // This is where you'll add your rates
        }
    }
}
```

## Defining settings/options

You can then define your options using the settings API. In the snippets above you'll notice we init_form_fields and init_settings. These load up the settings API. To see how to add settings, see [WooCommerce settings API](https://woocommerce.com/document/settings-api/).

## The calculate_shipping() method

`calculate_shipping()`` is a method which you use to add your rates - WooCommerce will call this when doing shipping calculations. Do your plugin specific calculations here and then add the rates via the API. How do you do that? Like so:

```php
$rate = array(
    'label'    => "Label for the rate",
    'cost'     => '10.99',
    'calc_tax' => 'per_item'
);

// Register the rate
$this->add_rate( $rate );
```

Add_rate takes an array of options. The defaults/possible values for the array are as follows:

```php
$defaults = array(
    'label' => '',   // Label for the rate
    'cost'  => '0',  // Amount for shipping or an array of costs (for per item shipping)
    'taxes' => '',   // Pass an array of taxes, or pass nothing to have it calculated for you, or pass 'false' to calculate no tax for this method
    'calc_tax' => 'per_order' // Calc tax per_order or per_item. Per item needs an array of costs passed via 'cost'
);
```

Your shipping method can pass as many rates as you want - just ensure that the id for each is different. The user will get to choose rate during checkout.

## Piecing it all together

The skeleton shipping method code all put together looks like this:

```php
<?php
/*
Plugin Name: Your Shipping plugin
Plugin URI: https://woocommerce.com/
Description: Your shipping method plugin
Version: 1.0.0
Author: WooThemes
Author URI: https://woocommerce.com/
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function your_shipping_method_init() {
		if ( ! class_exists( 'WC_Your_Shipping_Method' ) ) {
			class WC_Your_Shipping_Method extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'your_shipping_method'; // Id for your shipping method. Should be uunique.
					$this->method_title       = __( 'Your Shipping Method' );  // Title shown in admin
					$this->method_description = __( 'Description of your shipping method' ); // Description shown in admin

					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->title              = "My Shipping Method"; // This can be added as an setting but for this example its forced.

					$this->init();
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param array $package
				 * @return void
				 */
				public function calculate_shipping( $package = array() ) {
					$rate = array(
						'label' => $this->title,
						'cost' => '10.99',
						'calc_tax' => 'per_item'
					);

					// Register the rate
					$this->add_rate( $rate );
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'your_shipping_method_init' );

	function add_your_shipping_method( $methods ) {
		$methods['your_shipping_method'] = 'WC_Your_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_your_shipping_method' );
}
```
