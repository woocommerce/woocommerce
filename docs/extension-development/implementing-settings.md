---
post_title: Creating custom settings for WooCommerce extensions
menu_title: Creating custom settings
tags: how-to
---

If you're customizing WooCommerce or adding your own functionality to it you'll probably need a settings page of some sort. One of the easiest ways to create a settings page is by taking advantage of the [`WC_Integration` class](https://woocommerce.github.io/code-reference/classes/WC-Integration.html 'WC_Integration Class'). Using the Integration class will automatically create a new settings page under **WooCommerce > Settings > Integrations** and it will automatically save, and sanitize your data for you. We've created this tutorial so you can see how to create a new integration.

## Setting up the Integration

You'll need at least two files to create an integration so you'll need to create a directory.

### Creating the Main Plugin File

Create your main plugin file to [hook](https://developer.wordpress.org/reference/functions/add_action/ 'WordPress add_action()') into the `plugins_loaded` hook and check if the `WC_Integration` [class exists](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.extends 'PHP Class Exists'). If it doesn't then the user most likely doesn't have WooCommerce activated. After you do that you need to register the integration. Load the integration file (we'll get to this file in a minute). Use the `woocommerce_integrations` filter to add a new integration to the [array](http://php.net/manual/en/language.types.array.php 'PHP Array').

### Creating the Integration Class

Now that we have the framework setup let's actually implement this Integration class. There already is a `WC_Integration` class so we want to make a [child class](http://php.net/manual/en/keyword.extends.php 'PHP Child Class'). This way it inherits all of the existing methods and data. You'll need to set an id, a description, and a title for your integration. These will show up on the integration page. You'll also need to load the settings by calling: `$this->init_form_fields();` & `$this->init_settings();` You'll also need to save your options by calling the `woocommerce_update_options_integration_{your method id}` hook. Lastly you have to input some settings to save! We've included two dummy fields below but we'll go more into fields in the next section.

> Added to a file named `class-wc-integration-demo-integration.php`

```php
<?php
/**
 * Integration Demo Integration.
 *
 * @package  WC_Integration_Demo_Integration
 * @category Integration
 * @author   Patrick Rauland
 */
if ( ! class_exists( 'WC_Integration_Demo_Integration' ) ) :
    /**
     * Demo Integration class.
     */
    class WC_Integration_Demo_Integration extends WC_Integration {
        /**
         * Init and hook in the integration.
         */
        public function __construct() {
            global $woocommerce;

            $this->id                 = 'integration-demo';
            $this->method_title       = __( 'Integration Demo', 'woocommerce-integration-demo' );
            $this->method_description = __( 'An integration demo to show you how easy it is to extend WooCommerce.', 'woocommerce-integration-demo' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->api_key = $this->get_option( 'api_key' );
            $this->debug   = $this->get_option( 'debug' );

            // Actions.
            add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initialize integration settings form fields.
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'api_key' => array(
                    'title'       => __( 'API Key', 'woocommerce-integration-demo' ),
                    'type'        => 'text',
                    'description' => __( 'Enter with your API Key. You can find this in "User Profile" drop-down (top right corner) > API Keys.', 'woocommerce-integration-demo' ),
                    'desc_tip'    => true,
                    'default'     => '',
                ),
                'debug' => array(
                    'title'       => __( 'Debug Log', 'woocommerce-integration-demo' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable logging', 'woocommerce-integration-demo' ),
                    'default'     => 'no',
                    'description' => __( 'Log events such as API requests', 'woocommerce-integration-demo' ),
                ),
            );
        }
    }
endif;
```

> Added to a file named `wc-integration-demo.php`

```php
<?php
/**
 * Plugin Name: WooCommerce Integration Demo
 * Plugin URI: https://gist.github.com/BFTrick/091d55feaaef0c5341d8
 * Description: A plugin demonstrating how to add a new WooCommerce integration.
 * Author: Patrick Rauland
 * Author URI: http://speakinginbytes.com/
 * Version: 1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
if ( ! class_exists( 'WC_Integration_Demo' ) ) :
    /**
     * Integration demo class.
     */
    class WC_Integration_Demo {
        /**
         * Construct the plugin.
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        /**
         * Initialize the plugin.
         */
        public function init() {
            // Checks if WooCommerce is installed.
            if ( class_exists( 'WC_Integration' ) ) {
                // Include our integration class.
                include_once 'class-wc-integration-demo-integration.php';
                // Register the integration.
                add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
            } else {
                // throw an admin error if you like
            }
        }

        /**
         * Add a new integration to WooCommerce.
         *
         * @param array Array of integrations.
         */
        public function add_integration( $integrations ) {
            $integrations[] = 'WC_Integration_Demo_Integration';
            return $integrations;
        }
    }
endif;

$WC_Integration_Demo = new WC_Integration_Demo( __FILE__ );

```

## Creating Settings

If you took a look through the last section you'll see that we added two dummy settings using the `init_form_fields()` method.

### Types of Settings

WooCommerce includes support for 8 types of settings.

-   text
-   price
-   decimal
-   password
-   textarea
-   checkbox
-   select
-   multiselect

And these settings have attributes which you can use. These affect the way the setting looks and behaves on the settings page. It doesn't affect the setting itself. The attributes will manifest slightly differently depending on the setting type. A placeholder for example doesn't work with checkboxes. To see exactly how they work you should look through the [source code](https://github.com/woocommerce/woocommerce/blob/master/includes/abstracts/abstract-wc-settings-api.php#L180 'WC Settings API on GitHub'). Ex.

-   title
-   class
-   css
-   placeholder
-   description
-   default
-   desc_tip

### Creating Your Own Settings

The built-in settings are great but you may need extra controls to create your settings page. That's why we included some methods to do this for you. First, define a setting by adding it to the `$this->form_fields` array, entering the kind of form control you want under `type`. You can override the default HTML for your form inputs by creating a method with a name of the format `generate_{ type }_html` which outputs HTML markup. To specify how buttons are rendered, you'd add a method called `generate_button_html`. For textareas, you'd add a `generate_textarea_html` method, and so on. (Check out the `generate_settings_html` method of the `WC_Settings_API` class in the WooCommerce source code to see how WooCommerce uses this.) The below example creates a button that goes to Woo.com.

```php
/**
 * Initialize integration settings form fields.
 *
 * @return void
 */
public function init_form_fields() {
	$this->form_fields = array(
		// don't forget to put your other settings here
		'customize_button' => array(
			'title'             => __( 'Customize!', 'woocommerce-integration-demo' ),
			'type'              => 'button',
			'custom_attributes' => array(
				'onclick' => "location.href='woocommerce.com'",
			),
			'description'       => __( 'Customize your settings by going to the integration site directly.', 'woocommerce-integration-demo' ),
			'desc_tip'          => true,
		)
	);
}


/**
 * Generate Button HTML.
 *
 * @access public
 * @param mixed $key
 * @param mixed $data
 * @since 1.0.0
 * @return string
 */
public function generate_button_html( $key, $data ) {
	$field    = $this->plugin_id . $this->id . '_' . $key;
	$defaults = array(
		'class'             => 'button-secondary',
		'css'               => '',
		'custom_attributes' => array(),
		'desc_tip'          => false,
		'description'       => '',
		'title'             => '',
	);

	$data = wp_parse_args( $data, $defaults );

	ob_start();
	?>
	<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			<?php echo $this->get_tooltip_html( $data ); ?>
		</th>
		<td class="forminp">
			<fieldset>
				<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
				<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
				<?php echo $this->get_description_html( $data ); ?>
			</fieldset>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}
```

## Validating & Sanitizing Data

To create the best user experience you'll most likely want to validate and sanitize your data. The integration class already performs basic sanitization so that there's no malicious code present but you could further sanitize by removing unused data. An example of sanitizing data would be integrating with a 3rd party service where all API keys are upper case. You could convert the API key to upper case which will make it a bit more clear for the user.

### Sanitize

We'll demonstrate how to sanitize data first because it's a bit easier to understand. But the one thing you should keep in mind is that sanitizing happens _after_ validation. So if something isn't validated it won't get to the sanitization step.

```php
/**
 * Init and hook in the integration.
 */
public function __construct() {

    // do other constructor stuff first

	// Filters.
	add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

}

/**
 * Sanitize our settings
 */
public function sanitize_settings( $settings ) {
	// We're just going to make the api key all upper case characters since that's how our imaginary API works
	if ( isset( $settings ) &&
	     isset( $settings['api_key'] ) ) {
		$settings['api_key'] = strtoupper( $settings['api_key'] );
	}
	return $settings;
}
```

### Validation

Validation isn't always necessary but it's nice to do. If your API keys are always 10 characters long and someone enters one that's not 10 then you can print out an error message and prevent the user a lot of headache when they assumed they put it in correctly. First set up a `validate_{setting key}_field` method for each field you want to validate. For example, with the `api_key` field you need a `validate_api_key_field()` method.

```php
public function validate_api_key_field( $key, $value ) {
    if ( isset( $value ) && 20 < strlen( $value ) ) {
        WC_Admin_Settings::add_error( esc_html__( 'Looks like you made a mistake with the API Key field. Make sure it isn&apos;t longer than 20 characters', 'woocommerce-integration-demo' ) );
    }

    return $value;
}
```

## A complete example

If you've been following along you should have a complete integration example. If you have any problems see our [full integration demo](https://github.com/woogists/woocommerce-integration-demo 'Integration Demo').
