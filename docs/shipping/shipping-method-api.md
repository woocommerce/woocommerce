---
post_title: Shipping method API
menu_title: Shipping method API
tags: reference
---

WooCommerce has a Shipping Method API which plugins can use to add their own rates. This article will take you through the steps to creating a new shipping method based on the core Flat Rate method that interacts with the Shipping API.

## Create a plugin

First off, create a regular WordPress/WooCommerce plugin - see our [Building Your First Extension guide](../extension-development/building-your-first-extension.md) to get started. You'll define your shipping method class in this plugin file and maintain it outside of WooCommerce.

## Checking for WooCommerce and creating a function to house your class

Before we attempt to load our class, we need to make sure that WooCommerce is active and the primary classes and functions necessary for us to work are available. Once we know those are available, we can add an action to initialize our class, and a filter to add our method to the primary shipping method list.

```php
/**
 * If WooCommerce is active, then these classes and function will exist.
 */
if ( class_exists( 'woocommerce' ) && class_exists( 'WC_Shipping_Method' ) && function_exists( 'WC' ) ) {
	// We add an action to init our shipping method class, and a filter to add our shipping method to the method list.
	add_action( 'woocommerce_shipping_init', 'your_shipping_method_init' );
	add_filter( 'woocommerce_shipping_methods', 'your_shipping_method_add' );
}

/**
 * Your function to add your shipping method to the shipping method list.
 */
function your_shipping_method_add( $methods ) {
	$methods['your_shipping_method'] = 'WC_Your_Shipping_Method';
	return $methods;
}

/**
 * Your function to init your shipping method class.
 */
function your_shipping_method_init() {
    // Your class will go here
}
```

## Create your class

Now we create your class to place inside the function just created. It is best to make sure the class doesn't already exist to make sure there are no unexpected fatal errors. The class will need to extend the shipping method class so that we have access to the Shipping and Settings APIs. We also make sure to declare neccessary properties and then define more options/properties in the constructor, and we end with a call to the init method.

```php
if ( ! class_exists( 'WC_Your_Shipping_Method' ) ) {
	class WC_Your_Shipping_Method extends WC_Shipping_Method {
		/**
		 * Shipping method cost.
		 *
		 * @var string
		 */
		public $cost;

		/**
		 * Shipping method type.
		 *
		 * @var string
		 */
		public $type;

		/**
		 * Constructor for your shipping class.
		 *
		 * @param  int  $instance_id Shipping method instance ID. A new instance ID is assigned per instance created in a shipping zone.
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'your_shipping_method'; // ID for your shipping method. Should be unique.
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Your Shipping Method', 'your_text_domain' );  // Title shown in admin.
			$this->method_description = __( 'Description of your shipping method', 'your_text_domain' ); // Description shown in admin.
			$this->supports           = array(
				'settings',                // Provides a stand alone settings tab for your shipping method under WooCommerce > Settings > Shipping.
				'shipping-zones',          // Allows merchants to add your shipping method to shipping zones.
				'instance-settings',       // Allows for a page where merchants can edit the instance of your method included in a shipping zone.
				'instance-settings-modal', // Allows for a modal where merchants can edit the instance of your method included in a shipping zone.
			);

			// Additional initialization of the shipping method.
			$this->init();
		}
	}
}
```

## Defining settings/options

We want to make sure to add an action for the shipping method when options are updated to call `process_admin_options`, this will save any settings entered by a merchant/admin when they choose to save.

Options are then able to be defined using the [WooCommerce settings API](https://developer.woocommerce.com/docs/settings-api/). We have two additional methods `init_form_fields` and `init_instance_form_fields` that will initialize the form fields for both the stand alone settings and instance settings for us. Additional options, such as the title, tax status, etc, are set via the `get_option` method of the Settings API. These are set for either the stand alone page, or the instance settings, whichever is being displayed.


```php
	/**
	 * Additional initialization of options for the shipping method not necessary in the constructor.
	 *
	 * @return void
	 */
	public function init() {
		// Save settings in admin if any have been defined. (using Shipping/Settings API)
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		// Init stand alone settings and also the instance settings form fields.
		$this->init_form_fields();
		$this->init_instance_form_fields();

		// Use the Settings API to get the saved options to use for the settings fields.
		$this->title      = $this->get_option( 'title' );
		$this->tax_status = $this->get_option( 'tax_status' );
		$this->cost       = $this->get_option( 'cost' );
		$this->type       = $this->get_option( 'type', 'class' );
	}

	/**
	 * Our method to initialize our form fields for our stand alone settings page, if needed.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		// Set the form_fields property to an array that will be able to be used by the Settings API to show the fields on the page.
		$this->form_fields = array(
			'title'      => array(
				'title'       => __( 'Name', 'your_text_domain' ),
				'type'        => 'text',
				'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'your_text_domain' ),
				'default'     => __( 'Your shipping method', 'your_text_domain' ),
				'placeholder' => __( 'e.g. Standard national', 'your_text_domain' ),
				'desc_tip'    => true, // Include this if you would like your description to show as a tooltip.
			),
			'tax_status' => array(
				'title'   => __( 'Tax status', 'your_text_domain' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'your_text_domain' ),
					'none'    => _x( 'None', 'Tax status', 'your_text_domain' ),
				),
			),
			'cost'       => array(
				'title'             => __( 'Cost', 'your_text_domain' ),
				'type'              => 'text',
				'placeholder'       => '',
				'description'       => __( 'Enter a cost (excl. tax).', 'your_text_domain' ),
				'default'           => '0',
				'desc_tip'          => true,
				'sanitize_callback' => array( $this, 'sanitize_cost' ),
			),
		);
	}

	/**
	 * Our method to initialize our form fields for separate instances.
	 *
	 * @return void
	 */
	private function init_instance_form_fields() {
		// Define some strings that will be used several times for the cost decription and link.
		$cost_desc = __( 'Enter a cost (excl. tax).', 'your_text_domain' );
		$cost_link = sprintf( '<span id="wc-shipping-advanced-costs-help-text">%s <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#advanced-costs">%s</a>.</span>', __( 'Charge a flat rate per item, or enter a cost formula to charge a percentage based cost or a minimum fee. Learn more about', 'your_text_domain' ), __( 'advanced costs', 'your_text_domain' ) );

		// Start the array of fields.
		$fields = array(
			'title'      => array(
				'title'       => __( 'Name', 'your_text_domain' ),
				'type'        => 'text',
				'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'your_text_domain' ),
				'default'     => __( 'Your shipping method', 'your_text_domain' ),
				'placeholder' => __( 'e.g. Standard national', 'your_text_domain' ),
				'desc_tip'    => true,
			),
			'tax_status' => array(
				'title'   => __( 'Tax status', 'your_text_domain' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'your_text_domain' ),
					'none'    => _x( 'None', 'Tax status', 'your_text_domain' ),
				),
			),
			'cost'       => array(
				'title'             => __( 'Cost', 'your_text_domain' ),
				'type'              => 'text',
				'class'             => 'wc-shipping-modal-price',
				'placeholder'       => '',
				'description'       => $cost_desc,
				'default'           => '0',
				'desc_tip'          => true,
				'sanitize_callback' => array( $this, 'sanitize_cost' ),
			),
		);

		/**
		 * Flat rate shipping has the ability to add rates per shipping class, so here we get the shipping classes and then provide fields
		 * for merchants/admins to use to be able to specify these costs. 
		 */
		$shipping_classes = WC()->shipping()->get_shipping_classes();

		if ( ! empty( $shipping_classes ) ) {
			$fields['class_costs'] = array(
				'title'       => __( 'Shipping class costs', 'your_text_domain' ),
				'type'        => 'title',
				'default'     => '',
				/* translators: %s: URL for link */
				'description' => sprintf( __( 'These costs can optionally be added based on the <a target="_blank" href="%s">product shipping class</a>. Learn more about <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#shipping-classes">setting shipping class costs</a>.', 'your_text_domain' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
			);
			foreach ( $shipping_classes as $shipping_class ) {
				if ( ! isset( $shipping_class->term_id ) ) {
					continue;
				}
				$fields[ 'class_cost_' . $shipping_class->term_id ] = array(
					/* translators: %s: shipping class name */
					'title'             => sprintf( __( '"%s" shipping class cost', 'your_text_domain' ), esc_html( $shipping_class->name ) ),
					'type'              => 'text',
					'class'             => 'wc-shipping-modal-price',
					'placeholder'       => __( 'N/A', 'your_text_domain' ),
					'description'       => $cost_desc,
					'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ),
					'desc_tip'          => true,
					'sanitize_callback' => array( $this, 'sanitize_cost' ),
				);
			}

			$fields['no_class_cost'] = array(
				'title'             => __( 'No shipping class cost', 'your_text_domain' ),
				'type'              => 'text',
				'class'             => 'wc-shipping-modal-price',
				'placeholder'       => __( 'N/A', 'your_text_domain' ),
				'description'       => $cost_desc,
				'default'           => '',
				'desc_tip'          => true,
				'sanitize_callback' => array( $this, 'sanitize_cost' ),
			);

			$fields['type'] = array(
				'title'       => __( 'Calculation type', 'your_text_domain' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'class',
				'options'     => array(
					'class' => __( 'Per class: Charge shipping for each shipping class individually', 'your_text_domain' ),
					'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'your_text_domain' ),
				),
				'description' => $cost_link,
			);
		}

		// And finally we set the instance_form_fields property for the Shipping API to use.
		$this->instance_form_fields = $fields;
	}
```

## Sanitizing the cost

In the previous code snippets you can see that there is a `sanitize_callback` to use `sanitize_cost`, while this may not always be needed, it is still good to keep sanitizing user input in mind. Since a cost field will need to be a text field to allow for a decimal separator, we need to make sure the value entered is valid. For this, we introduce the below method.

```php
	/**
	 * Sanitize the cost value.
	 * This method is called when the `sanitize_callback` method is called in the Settings API while the values input by the merchant are being saved.
	 *
	 * @param string $value Unsanitized value.
	 * @return string
	 * @throws Exception If the cost is not numeric.
	 */
	public function sanitize_cost( $value ) {
		// If the value is null, then set it to zero. Run the value through WordPress core sanitization functions, the remove the currency symbol, if present.
		$value = is_null( $value ) ? '0' : $value;
		$value = wp_kses_post( trim( wp_unslash( $value ) ) );
		$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

		// Get the current locale and all possible decimal separators.
		$locale   = localeconv();
		$decimals = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );

		// Remove whitespace, then decimals, and then invalid start/end characters.
		$value = preg_replace( '/\s+/', '', $value );
		$value = str_replace( $decimals, '.', $value );
		$value = rtrim( ltrim( $value, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// If the value is not numeric, then throw an exception.
		if ( ! is_numeric( $value ) ) {
			throw new Exception( 'Invalid cost entered.' );
		}
		return $value;
	}
```

## Adding a rate

Rates are added via the Shipping API using the `add_rate` method. Below is a breakdown of available options, and in the next section we will put this to use.

Your shipping method can pass as many rates as needed, just ensure that the id for each is different. The user will get to choose rate during checkout.

```php
$rate = array(
    'label' => '',   // Label for the rate.
    'cost'  => '0',  // Amount for shipping or an array of costs (for per item shipping).
    'taxes' => '',   // Pass an array of taxes, or pass nothing to have it calculated for you, or pass 'false' to calculate no tax for this method.
    'calc_tax' => 'per_order' // Calc tax per_order or per_item. Per item needs an array of costs passed via 'cost'.
);

// Register the rate
$this->add_rate( $rate );
```

## The calculate_shipping() method

The method to use to add your rates is `calculate_shipping`, WooCommerce will call this when doing shipping calculations in the cart and checkout. Do your plugin specific calculations here and then add the rates via the Shipping API.

Here we also include the `find_shipping_classes` method, which gets the shipping classes from each item in the package.

```php
	/**
	* Calculate the shipping costs.
	*
	* @param array $package Package of items from cart.
	*/
	public function calculate_shipping( $package = array() ) {
		// Get the rate set for this instance.
		$rate = array(
			'id'      => $this->get_rate_id(), // Get the instance rate ID from the Shipping API.
			'label'   => $this->title,
			'cost'    => 0,
			'package' => $package,
		);

		// Calculate the costs.
		$has_costs = false; // True when a cost is set. False if all costs are blank strings.
		$cost      = $this->get_option( 'cost' );

		// If a cost has been set, then we evaluate the cost to make sure it is valid.
		if ( '' !== $cost ) {
			$has_costs    = true;
			$rate['cost'] = $cost;
		}

		// Flat rate shipping has the ability to set a cost per shipping class, so here we get the shipping classes and add those costs, as well.
		$shipping_classes = WC()->shipping()->get_shipping_classes();
		if ( ! empty( $shipping_classes ) ) {
			// Check to see if there are shipping classes assigned to the products in the package/cart.
			$found_shipping_classes = $this->find_shipping_classes( $package );
			$highest_class_cost     = 0;

			// If shipping classes are found to be assigned to the products, then we go through each shipping class.
			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				// Also handles backwards compatibility when slugs were used instead of ids.
				$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
				$class_cost   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option( 'class_cost_' . $shipping_class_term->term_id, $this->get_option( 'class_cost_' . $shipping_class, '' ) ) : $this->get_option( 'no_class_cost', '' );

				// If a cost is not assigned to the shipping class, then we move to the next class.
				if ( '' === $class_cost ) {
					continue;
				}

				// We have a shipping class cost, so we evaluate the class cost to confirm it is valid.
				$has_costs = true;

				/**
				* Flat rate has two options when it comes to shipping class, per class or per order.
				* Here we check that setting so we can apply the costs accordingly.
				*/
				if ( 'class' === $this->type ) {
					$rate['cost'] += $class_cost;
				} else {
					$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
				}
			}

			// If the cost is per order, then we apply the highest class cost to the base cost.
			if ( 'order' === $this->type && $highest_class_cost ) {
				$rate['cost'] += $highest_class_cost;
			}
		}

		if ( $has_costs ) {
			$this->add_rate( $rate );
		}

		/**
		* Developers can add additional flat rates based on this one via this action since @version 2.4.
		*
		* Previously there were (overly complex) options to add additional rates however this was not user.
		* friendly and goes against what Flat Rate Shipping was originally intended for.
		*/
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	/**
	* Finds and returns shipping classes and the products with said class.
	*
	* @param mixed $package Package of items from cart.
	* @return array
	*/
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}
```

## Piecing it all together

Our trimmed down version of the Flat Rate shipping method as a stand alone plugin looks like this:

```php
<?php
/**
 * Plugin Name: Your Shipping plugin
 * Plugin URI: https://woocommerce.com/
 * Description: Your shipping method plugin
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: your_text_domain
 */

/**
 * If WooCommerce is active, then the class will exist.
 */
if ( class_exists( 'woocommerce' ) && class_exists( 'WC_Shipping_Method' ) && function_exists( 'WC' ) ) {
	// We add an action to init our shipping method class, and a filter to add our shipping method to the method list.
	add_action( 'woocommerce_shipping_init', 'your_shipping_method_init' );
	add_filter( 'woocommerce_shipping_methods', 'your_shipping_method_add' );
}

/**
 * Your function to add your shipping method to the shipping method list.
 */
function your_shipping_method_add( $methods ) {
	$methods['your_shipping_method'] = 'WC_Your_Shipping_Method';
	return $methods;
}

/**
 * Your function to init your shipping method class.
 */
function your_shipping_method_init() {
	/**
	 * Ideally this would include the class from another file, but for demonstration purposes we will include it here.
	 */
	if ( ! class_exists( 'WC_Your_Shipping_Method' ) ) {
		class WC_Your_Shipping_Method extends WC_Shipping_Method {
			/**
			 * Shipping method cost.
			 *
			 * @var string
			 */
			public $cost;

			/**
			 * Shipping method type.
			 *
			 * @var string
			 */
			public $type;

			/**
			 * Constructor for your shipping class.
			 *
			 * @param  int  $instance_id Shipping method instance ID. A new instance ID is assigned per instance created in a shipping zone.
			 * @return void
			 */
			public function __construct( $instance_id = 0 ) {
				$this->id                 = 'your_shipping_method'; // ID for your shipping method. Should be unique.
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = __( 'Your Shipping Method', 'your_text_domain' );  // Title shown in admin.
				$this->method_description = __( 'Description of your shipping method', 'your_text_domain' ); // Description shown in admin.
				$this->supports           = array(
					'settings',                // Provides a stand alone settings tab for your shipping method under WooCommerce > Settings > Shipping.
					'shipping-zones',          // Allows merchants to add your shipping method to shipping zones.
					'instance-settings',       // Allows for a page where merchants can edit the instance of your method included in a shipping zone.
					'instance-settings-modal', // Allows for a modal where merchants can edit the instance of your method included in a shipping zone.
				);

				// Additional initialization of the shipping method.
				$this->init();
			}

			/**
			 * Additional initialization of options for the shipping method not necessary in the constructor.
			 *
			 * @return void
			 */
			public function init() {
				// Save settings in admin if any have been defined. (using Shipping/Settings API)
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

				// Init stand alone settings and also the instance settings form fields.
				$this->init_form_fields();
				$this->init_instance_form_fields();

				// Use the Settings API to get the saved options to use for the settings fields.
				$this->title      = $this->get_option( 'title' );
				$this->tax_status = $this->get_option( 'tax_status' );
				$this->cost       = $this->get_option( 'cost' );
				$this->type       = $this->get_option( 'type', 'class' );
			}

			/**
			 * Calculate the shipping costs.
			 *
			 * @param array $package Package of items from cart.
			 */
			public function calculate_shipping( $package = array() ) {
				// Get the rate set for this instance.
				$rate = array(
					'id'      => $this->get_rate_id(), // Get the instance rate ID from the Shipping API.
					'label'   => $this->title,
					'cost'    => 0,
					'package' => $package,
				);

				// Calculate the costs.
				$has_costs = false; // True when a cost is set. False if all costs are blank strings.
				$cost      = $this->get_option( 'cost' );

				// If a cost has been set, then we evaluate the cost to make sure it is valid.
				if ( '' !== $cost ) {
					$has_costs    = true;
					$rate['cost'] = $cost;
				}

				// Flat rate shipping has the ability to set a cost per shipping class, so here we get the shipping classes and add those costs, as well.
				$shipping_classes = WC()->shipping()->get_shipping_classes();
				if ( ! empty( $shipping_classes ) ) {
					// Check to see if there are shipping classes assigned to the products in the package/cart.
					$found_shipping_classes = $this->find_shipping_classes( $package );
					$highest_class_cost     = 0;

					// If shipping classes are found to be assigned to the products, then we go through each shipping class.
					foreach ( $found_shipping_classes as $shipping_class => $products ) {
						// Also handles backwards compatibility when slugs were used instead of ids.
						$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
						$class_cost   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option( 'class_cost_' . $shipping_class_term->term_id, $this->get_option( 'class_cost_' . $shipping_class, '' ) ) : $this->get_option( 'no_class_cost', '' );

						// If a cost is not assigned to the shipping class, then we move to the next class.
						if ( '' === $class_cost ) {
							continue;
						}

						// We have a shipping class cost, so we evaluate the class cost to confirm it is valid.
						$has_costs = true;

						/**
						 * Flat rate has two options when it comes to shipping class, per class or per order.
						 * Here we check that setting so we can apply the costs accordingly.
						 */
						if ( 'class' === $this->type ) {
							$rate['cost'] += $class_cost;
						} else {
							$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
						}
					}

					// If the cost is per order, then we apply the highest class cost to the base cost.
					if ( 'order' === $this->type && $highest_class_cost ) {
						$rate['cost'] += $highest_class_cost;
					}
				}

				if ( $has_costs ) {
					$this->add_rate( $rate );
				}

				/**
				 * Developers can add additional flat rates based on this one via this action since @version 2.4.
				 *
				 * Previously there were (overly complex) options to add additional rates however this was not user.
				 * friendly and goes against what Flat Rate Shipping was originally intended for.
				 */
				do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
			}

			/**
			 * Finds and returns shipping classes and the products with said class.
			 *
			 * @param mixed $package Package of items from cart.
			 * @return array
			 */
			public function find_shipping_classes( $package ) {
				$found_shipping_classes = array();

				foreach ( $package['contents'] as $item_id => $values ) {
					if ( $values['data']->needs_shipping() ) {
						$found_class = $values['data']->get_shipping_class();

						if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
							$found_shipping_classes[ $found_class ] = array();
						}

						$found_shipping_classes[ $found_class ][ $item_id ] = $values;
					}
				}

				return $found_shipping_classes;
			}

			/**
			 * Sanitize the cost value.
			 * This method is called when the `sanitize_callback` method is called in the Settings API while the values input by the merchant are being saved.
			 *
			 * @param string $value Unsanitized value.
			 * @return string
			 * @throws Exception If the cost is not numeric.
			 */
			public function sanitize_cost( $value ) {
				// If the value is null, then set it to zero. Run the value through WordPress core sanitization functions, the remove the currency symbol, if present.
				$value = is_null( $value ) ? '0' : $value;
				$value = wp_kses_post( trim( wp_unslash( $value ) ) );
				$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

				// Get the current locale and all possible decimal separators.
				$locale   = localeconv();
				$decimals = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );

				// Remove whitespace, then decimals, and then invalid start/end characters.
				$value = preg_replace( '/\s+/', '', $value );
				$value = str_replace( $decimals, '.', $value );
				$value = rtrim( ltrim( $value, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

				// If the value is not numeric, then throw an exception.
				if ( ! is_numeric( $value ) ) {
					throw new Exception( 'Invalid cost entered.' );
				}
				return $value;
			}

			/**
			 * Our method to initialize our form fields for our stand alone settings page, if needed.
			 *
			 * @return void
			 */
			public function init_form_fields() {
				// Set the form_fields property to an array that will be able to be used by the Settings API to show the fields on the page.
				$this->form_fields = array(
					'title'      => array(
						'title'       => __( 'Name', 'your_text_domain' ),
						'type'        => 'text',
						'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'your_text_domain' ),
						'default'     => __( 'Your shipping method', 'your_text_domain' ),
						'placeholder' => __( 'e.g. Standard national', 'your_text_domain' ),
						'desc_tip'    => true, // Include this if you would like your description to show as a tooltip.
					),
					'tax_status' => array(
						'title'   => __( 'Tax status', 'your_text_domain' ),
						'type'    => 'select',
						'class'   => 'wc-enhanced-select',
						'default' => 'taxable',
						'options' => array(
							'taxable' => __( 'Taxable', 'your_text_domain' ),
							'none'    => _x( 'None', 'Tax status', 'your_text_domain' ),
						),
					),
					'cost'       => array(
						'title'             => __( 'Cost', 'your_text_domain' ),
						'type'              => 'text',
						'placeholder'       => '',
						'description'       => __( 'Enter a cost (excl. tax).', 'your_text_domain' ),
						'default'           => '0',
						'desc_tip'          => true,
						'sanitize_callback' => array( $this, 'sanitize_cost' ),
					),
				);
			}

			/**
			 * Our method to initialize our form fields for separate instances.
			 *
			 * @return void
			 */
			private function init_instance_form_fields() {
				// Define some strings that will be used several times for the cost decription and link.
				$cost_desc = __( 'Enter a cost (excl. tax).', 'your_text_domain' );
				$cost_link = sprintf( '<span id="wc-shipping-advanced-costs-help-text">%s <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#advanced-costs">%s</a>.</span>', __( 'Charge a flat rate per item, or enter a cost formula to charge a percentage based cost or a minimum fee. Learn more about', 'your_text_domain' ), __( 'advanced costs', 'your_text_domain' ) );

				// Start the array of fields.
				$fields = array(
					'title'      => array(
						'title'       => __( 'Name', 'your_text_domain' ),
						'type'        => 'text',
						'description' => __( 'Your customers will see the name of this shipping method during checkout.', 'your_text_domain' ),
						'default'     => __( 'Your shipping method', 'your_text_domain' ),
						'placeholder' => __( 'e.g. Standard national', 'your_text_domain' ),
						'desc_tip'    => true,
					),
					'tax_status' => array(
						'title'   => __( 'Tax status', 'your_text_domain' ),
						'type'    => 'select',
						'class'   => 'wc-enhanced-select',
						'default' => 'taxable',
						'options' => array(
							'taxable' => __( 'Taxable', 'your_text_domain' ),
							'none'    => _x( 'None', 'Tax status', 'your_text_domain' ),
						),
					),
					'cost'       => array(
						'title'             => __( 'Cost', 'your_text_domain' ),
						'type'              => 'text',
						'class'             => 'wc-shipping-modal-price',
						'placeholder'       => '',
						'description'       => $cost_desc,
						'default'           => '0',
						'desc_tip'          => true,
						'sanitize_callback' => array( $this, 'sanitize_cost' ),
					),
				);

				/**
				 * Flat rate shipping has the ability to add rates per shipping class, so here we get the shipping classes and then provide fields
				 * for merchants/admins to use to be able to specify these costs. 
				 */
				$shipping_classes = WC()->shipping()->get_shipping_classes();

				if ( ! empty( $shipping_classes ) ) {
					$fields['class_costs'] = array(
						'title'       => __( 'Shipping class costs', 'your_text_domain' ),
						'type'        => 'title',
						'default'     => '',
						/* translators: %s: URL for link */
						'description' => sprintf( __( 'These costs can optionally be added based on the <a target="_blank" href="%s">product shipping class</a>. Learn more about <a target="_blank" href="https://woocommerce.com/document/flat-rate-shipping/#shipping-classes">setting shipping class costs</a>.', 'your_text_domain' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
					);
					foreach ( $shipping_classes as $shipping_class ) {
						if ( ! isset( $shipping_class->term_id ) ) {
							continue;
						}
						$fields[ 'class_cost_' . $shipping_class->term_id ] = array(
							/* translators: %s: shipping class name */
							'title'             => sprintf( __( '"%s" shipping class cost', 'your_text_domain' ), esc_html( $shipping_class->name ) ),
							'type'              => 'text',
							'class'             => 'wc-shipping-modal-price',
							'placeholder'       => __( 'N/A', 'your_text_domain' ),
							'description'       => $cost_desc,
							'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ),
							'desc_tip'          => true,
							'sanitize_callback' => array( $this, 'sanitize_cost' ),
						);
					}

					$fields['no_class_cost'] = array(
						'title'             => __( 'No shipping class cost', 'your_text_domain' ),
						'type'              => 'text',
						'class'             => 'wc-shipping-modal-price',
						'placeholder'       => __( 'N/A', 'your_text_domain' ),
						'description'       => $cost_desc,
						'default'           => '',
						'desc_tip'          => true,
						'sanitize_callback' => array( $this, 'sanitize_cost' ),
					);

					$fields['type'] = array(
						'title'       => __( 'Calculation type', 'your_text_domain' ),
						'type'        => 'select',
						'class'       => 'wc-enhanced-select',
						'default'     => 'class',
						'options'     => array(
							'class' => __( 'Per class: Charge shipping for each shipping class individually', 'your_text_domain' ),
							'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'your_text_domain' ),
						),
						'description' => $cost_link,
					);
				}

				// And finally we set the instance_form_fields property for the Shipping API to use.
				$this->instance_form_fields = $fields;
			}
		}
	}
}
```
