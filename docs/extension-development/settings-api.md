---
post_title: Settings API
---

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
	&lt;h2&gt;<?php esc_html_e( 'Your plugin name', 'your-text-domain' ); ?>&lt;/h2&gt;
	&lt;table class="form-table"&gt;
		<?php $this->generate_settings_html(); ?>
	&lt;/table&gt;
	<?php
}
```

This will output your settings in the correct format.

## Saving your settings

To have your settings save, add your class's `process_admin_options` method to the appropriate `_update_options_` hook. For example, payment gateways should use the payment gateway hook:

```php
add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
```

Other types of plugins have similar hooks:

```php
add_action( 'woocommerce_update_options_shipping_methods', array( $this, 'process_admin_options' ) );
```

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
