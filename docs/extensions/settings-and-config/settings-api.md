---
post_title: Settings API
sidebar_label: Settings API
sidebar_position: 1
---

# Settings API

The WooCommerce Settings API is used by extensions to display, save, and load settings. The best way to make use of the API in your extension is to create a class that extends the `WC_Settings_API` class:

```php
class My_Extension_Settings extends WC_Settings_API {
	//
}
```

## Defining form fields

You can define your fields using a method called `init_form_fields` in your class constructor:

```php
$this->init_form_fields();
```

You must have your settings defined before you can load them. Setting definitions go in the `form_fields` array:

```php
/**
 * Initialise gateway settings form fields.
 */
function init_form_fields() {
	$this->form_fields = array(
		'title'       => array(
			'title'       => __( 'Title', 'your-text-domain' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'your-text-domain' ),
			'default'     => __( 'PayPal', 'your-text-domain' )
		),
		'description' => array(
			'title'       => __( 'Description', 'your-text-domain' ),
			'type'        => 'textarea',
			'description' => __( 'This controls the description which the user sees during checkout.', 'your-text-domain' ),
			'default'     => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'your-text-domain' )
		)
	);
} // End init_form_fields()
```

(Make sure your class initializes the `form_fields` property so that the "Creation of dynamic property" error is not thrown in PHP 8.2+)

In the above example we define two settings, Title and Description. Title is a text box, whereas Description is a textarea. Notice how you can define a default value and a description for the setting itself.

Setting definitions use the following format:

```php
'setting_name' => array(
	'title'       => 'Title for your setting shown on the settings page',
	'description' => 'Description for your setting shown on the settings page',
	'type'        => 'text|password|textarea|checkbox|select|multiselect',
	'default'     => 'Default value for the setting',
	'class'       => 'Class for the input element',
	'css'         => 'CSS rules added inline on the input element',
	'label'       => 'Label', // For checkbox inputs only.
	'options'     => array( // Array of options for select/multiselect inputs only.
		'key' => 'value'
	),
)
```

## Displaying your settings

Create a method called `admin_options` containing the following:

```php
function admin_options() {
	?>
	<h2><?php esc_html_e( 'Your plugin name', 'your-text-domain' ); ?></h2>
	<table class="form-table">
		<?php $this->generate_settings_html(); ?>
	</table>
	<?php
}
```

This will output your settings in the correct format.

## Saving your settings

`WC_Settings_API` does not create a settings screen or save action by itself. The integration that renders the settings must call `process_admin_options()` when its form is submitted.

WooCommerce provides save actions for its registered settings integrations. A `WC_Payment_Gateway` subclass registered with WooCommerce should use the dynamic payment gateway action, which includes its gateway ID:

```php
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
```

A `WC_Shipping_Method` subclass registered with WooCommerce should use the dynamic shipping method action, which includes its method ID:

```php
add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
```

These actions are only fired for gateways or shipping methods registered with WooCommerce. If you extend `WC_Settings_API` directly, the code that renders your form must also provide its save handler.

## Loading your settings

In the constructor you can load the settings you previously defined:

```php
// Load the settings.
$this->init_settings();
```

After that you can load your settings from the settings API. The `init_settings` method above populates the settings variable for you:

```php
// Define user set variables
$this->title       = $this->settings['title'];
$this->description = $this->settings['description'];
```
