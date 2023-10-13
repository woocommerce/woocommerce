# Implementing Settings for Extensions

If you’re customizing WooCommerce or adding your own functionality to it you’ll probably need a settings page of some sort. One of the easiest ways to create a settings page is by taking advantage of the [`WC_Integration` class](https://woocommerce.com/wc-apidocs/class-WC_Integration.html 'WC_Integration Class'). Using the Integration class will automatically create a new settings page under **WooCommerce > Settings > Integrations** and it will automatically save, and sanitize your data for you. We’ve created this tutorial so you can see how to create a new integration.

**Note:** This is a **Developer level** doc. If you are unfamiliar with code/templates and resolving potential conflicts, select a [WooExpert or Developer](https://woocommerce.com/customizations/) for assistance. We are unable to provide support for customizations under our  [Support Policy](http://www.woocommerce.com/support-policy/).

## [Setting up the Integration](#section-1)

[↑ Back to top](#doc-title 'Back to top')

You’ll need at least two files to create an integration so you’ll need to create a directory.

### [Creating the Main Plugin File](#section-2)

[↑ Back to top](#doc-title 'Back to top')

Create your main plugin file to [hook](http://codex.wordpress.org/Function_Reference/add_action 'WordPress add_action()') into the `plugins_loaded` hook and check if the `WC_Integration` [class exists](http://php.net/manual/en/function.class-exists.php 'PHP Class Exists'). If it doesn’t then the user most likely doesn’t have WooCommerce activated. After you do that you need to register the integration. Load the integration file (we’ll get to this file in a minute). Use the `woocommerce_integrations` filter to add a new integration to the [array](http://php.net/manual/en/language.types.array.php 'PHP Array').

### [Creating the Integration Class](#section-3)

[↑ Back to top](#doc-title 'Back to top')

Now that we have the framework setup let’s actually implement this Integration class. There already is a `WC_Integration` class so we want to make a [child class](http://php.net/manual/en/keyword.extends.php 'PHP Child Class'). This way it inherits all of the existing methods and data. You’ll need to set an id, a description, and a title for your integration. These will show up on the integration page. You’ll also need to load the settings by calling: `$this->init_form_fields();` & `$this->init_settings();` You’ll also need to save your options by calling the `woocommerce_update_options_integration_{your method id}` hook. Lastly you have to input some settings to save! I’ve included two dummy fields below but we’ll go more into fields in the next section. .gist table { margin-bottom: 0; }

<?php

/\*\*

\* Integration Demo Integration.

\*

\* @package WC\_Integration\_Demo\_Integration

\* @category Integration

\* @author Patrick Rauland

\*/

if ( ! class\_exists( 'WC\_Integration\_Demo\_Integration' ) ) :

class WC\_Integration\_Demo\_Integration extends WC\_Integration {

/\*\*

\* Init and hook in the integration.

\*/

public function \_\_construct() {

global $woocommerce;

$this\->id = 'integration-demo';

$this\->method\_title = \_\_( 'Integration Demo', 'woocommerce-integration-demo' );

$this\->method\_description = \_\_( 'An integration demo to show you how easy it is to extend WooCommerce.', 'woocommerce-integration-demo' );

// Load the settings.

$this\->init\_form\_fields();

$this\->init\_settings();

// Define user set variables.

$this\->api\_key = $this\->get\_option( 'api\_key' );

$this\->debug = $this\->get\_option( 'debug' );

// Actions.

add\_action( 'woocommerce\_update\_options\_integration\_' . $this\->id, array( $this, 'process\_admin\_options' ) );

}

/\*\*

\* Initialize integration settings form fields.

\*/

public function init\_form\_fields() {

$this\->form\_fields = array(

'api\_key' => array(

'title' => \_\_( 'API Key', 'woocommerce-integration-demo' ),

'type' => 'text',

'description' => \_\_( 'Enter with your API Key. You can find this in "User Profile" drop-down (top right corner) > API Keys.', 'woocommerce-integration-demo' ),

'desc\_tip' => true,

'default' => ''

),

'debug' => array(

'title' => \_\_( 'Debug Log', 'woocommerce-integration-demo' ),

'type' => 'checkbox',

'label' => \_\_( 'Enable logging', 'woocommerce-integration-demo' ),

'default' => 'no',

'description' => \_\_( 'Log events such as API requests', 'woocommerce-integration-demo' ),

),

);

}

}

endif;

[view raw](https://gist.github.com/woogists/b5ba9df255dd27be1cea031443a97d9f/raw/f899649a981d851f81705e832bbb69c8f4414343/class-wc-integration-demo-integration.php) [class-wc-integration-demo-integration.php](https://gist.github.com/woogists/b5ba9df255dd27be1cea031443a97d9f#file-class-wc-integration-demo-integration-php) hosted with ❤ by [GitHub](https://github.com)

.gist table { margin-bottom: 0; }

<?php

/\*\*

\* Plugin Name: WooCommerce Integration Demo

\* Plugin URI: https://gist.github.com/BFTrick/091d55feaaef0c5341d8

\* Description: A plugin demonstrating how to add a new WooCommerce integration.

\* Author: Patrick Rauland

\* Author URI: http://speakinginbytes.com/

\* Version: 1.0

\*

\* This program is free software: you can redistribute it and/or modify

\* it under the terms of the GNU General Public License as published by

\* the Free Software Foundation, either version 3 of the License, or

\* (at your option) any later version.

\*

\* This program is distributed in the hope that it will be useful,

\* but WITHOUT ANY WARRANTY; without even the implied warranty of

\* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the

\* GNU General Public License for more details.

\*

\* You should have received a copy of the GNU General Public License

\* along with this program. If not, see <http://www.gnu.org/licenses/>.

\*

\*/

if ( ! class\_exists( 'WC\_Integration\_Demo' ) ) :

class WC\_Integration\_Demo {

/\*\*

\* Construct the plugin.

\*/

public function \_\_construct() {

add\_action( 'plugins\_loaded', array( $this, 'init' ) );

}

/\*\*

\* Initialize the plugin.

\*/

public function init() {

// Checks if WooCommerce is installed.

if ( class\_exists( 'WC\_Integration' ) ) {

// Include our integration class.

include\_once 'class-wc-integration-demo-integration.php';

// Register the integration.

add\_filter( 'woocommerce\_integrations', array( $this, 'add\_integration' ) );

} else {

// throw an admin error if you like

}

}

/\*\*

\* Add a new integration to WooCommerce.

\*/

public function add\_integration( $integrations ) {

$integrations\[\] = 'WC\_Integration\_Demo\_Integration';

return $integrations;

}

}

$WC\_Integration\_Demo = new WC\_Integration\_Demo( \_\_FILE\_\_ );

endif;

[view raw](https://gist.github.com/woogists/dd105d364a05202b9d7340cfd1928ae5/raw/b2ec79c0c5deabc7289709701abd9a83b2c1cac1/wc-integration-demo.php) [wc-integration-demo.php](https://gist.github.com/woogists/dd105d364a05202b9d7340cfd1928ae5#file-wc-integration-demo-php) hosted with ❤ by [GitHub](https://github.com)

[Creating Settings](#section-4)
-------------------------------

[↑ Back to top](#doc-title "Back to top")

If you took a look through the last section you’ll see that we added two dummy settings using the `init_form_fields()` method.

### [Types of Settings](#section-5)

[↑ Back to top](#doc-title "Back to top")

WooCommerce includes support for 8 types of settings.

*   text
*   price
*   decimal
*   password
*   textarea
*   checkbox
*   select
*   multiselect

And these settings have attributes which you can use. These affect the way the setting looks and behaves on the settings page. It doesn’t affect the setting itself. The attributes will manifest slightly differently depending on the setting type. A placeholder for example doesn’t work with checkboxes. To see exactly how they work you should look through the [source code](https://github.com/woocommerce/woocommerce/blob/master/includes/abstracts/abstract-wc-settings-api.php#L180 "WC Settings API on GitHub"). Ex.

*   title
*   class
*   css
*   placeholder
*   description
*   default
*   desc\_tip

### [Creating Your Own Settings](#section-6)

[↑ Back to top](#doc-title "Back to top")

The built-in settings are great but you may need extra controls to create your settings page. That’s why we included some methods to do this for you. First, define a setting by adding it to the `$this->form_fields` array, entering the kind of form control you want under `type`. You can override the default HTML for your form inputs by creating a method with a name of the format `generate_{ type }_html` which outputs HTML markup. To specify how buttons are rendered, you’d add a method called `generate_button_html`. For textareas, you’d add a `generate_textarea_html` method, and so on. (Check out the `generate_settings_html` method of the `WC_Settings_API` class in the WooCommerce source code to see how WooCommerce uses this.) The below example creates a button that goes to WooThemes.com. .gist table { margin-bottom: 0; }

/\*\*

\* Initialize integration settings form fields.

\*

\* @return void

\*/

public function init\_form\_fields() {

$this-\>form\_fields = array(

// don't forget to put your other settings here

'customize\_button' =\> array(

'title' =\> \_\_( 'Customize!', 'woocommerce-integration-demo' ),

'type' =\> 'button',

'custom\_attributes' =\> array(

'onclick' =\> "location.href='http://www.woothemes.com'",

),

'description' =\> \_\_( 'Customize your settings by going to the integration site directly.', 'woocommerce-integration-demo' ),

'desc\_tip' =\> true,

)

);

}

/\*\*

\* Generate Button HTML.

\*

\* @access public

\* @param mixed $key

\* @param mixed $data

\* @since 1.0.0

\* @return string

\*/

public function generate\_button\_html( $key, $data ) {

$field = $this-\>plugin\_id . $this-\>id . '\_' . $key;

$defaults = array(

'class' =\> 'button-secondary',

'css' =\> '',

'custom\_attributes' =\> array(),

'desc\_tip' =\> false,

'description' =\> '',

'title' =\> '',

);

$data = wp\_parse\_args( $data, $defaults );

ob\_start();

?\>

<tr valign\="top"\>

<th scope\="row" class\="titledesc"\>

<label for\="<?php echo esc\_attr( $field ); ?>"\><?php echo wp\_kses\_post( $data\['title'\] ); ?></label\>

<?php echo $this\->get\_tooltip\_html( $data ); ?>

</th\>

<td class\="forminp"\>

<fieldset\>

<legend class\="screen-reader-text"\><span\><?php echo wp\_kses\_post( $data\['title'\] ); ?></span\></legend\>

<button class\="<?php echo esc\_attr( $data\['class'\] ); ?>" type\="button" name\="<?php echo esc\_attr( $field ); ?>" id\="<?php echo esc\_attr( $field ); ?>" style\="<?php echo esc\_attr( $data\['css'\] ); ?>" <?php echo $this\->get\_custom\_attribute\_html( $data ); ?>\><?php echo wp\_kses\_post( $data\['title'\] ); ?></button\>

<?php echo $this\->get\_description\_html( $data ); ?>

</fieldset\>

</td\>

</tr\>

<?php

return ob\_get\_clean();

}

[view raw](https://gist.github.com/woogists/f009b638ba349d38698e18db94262f9c/raw/a652380f33b1243a3161e5d116365113a6f66c6b/wc-create-custom-setting.php) [wc-create-custom-setting.php](https://gist.github.com/woogists/f009b638ba349d38698e18db94262f9c#file-wc-create-custom-setting-php) hosted with ❤ by [GitHub](https://github.com)

[Validating & Sanitizing Data](#section-7)
------------------------------------------

[↑ Back to top](#doc-title "Back to top")

To create the best user experience you’ll most likely want to validate and sanitize your data. The integration class already performs basic sanitization so that there’s no malicious code present but you could further sanitize by removing unused data. An example of sanitizing data would be integrating with a 3rd party service where all API keys are upper case. You could convert the API key to upper case which will make it a bit more clear for the user.

### [Sanitize](#section-8)

[↑ Back to top](#doc-title "Back to top")

I’m going to show you how to sanitize data first because it’s a bit easier to understand. But the one thing you should keep in mind is that sanitizing happens _after_ validation. So if something isn’t validated it won’t get to the sanitization step. .gist table { margin-bottom: 0; }

/\*\*

\* Init and hook in the integration.

\*/

public function \_\_construct() {

// do other constructor stuff first

// Filters.

add\_filter( 'woocommerce\_settings\_api\_sanitized\_fields\_' . $this-\>id, array( $this, 'sanitize\_settings' ) );

}

/\*\*

\* Sanitize our settings

\*/

public function sanitize\_settings( $settings ) {

// We're just going to make the api key all upper case characters since that's how our imaginary API works

if ( isset( $settings ) &&

isset( $settings\['api\_key'\] ) ) {

$settings\['api\_key'\] = strtoupper( $settings\['api\_key'\] );

}

return $settings;

}

[view raw](https://gist.github.com/woogists/8da516cfc0391780393d9b1af0c0471d/raw/b4847dfd627c2792b4dfdc89c5013b4d7875c010/wc-sanitize-settings.php) [wc-sanitize-settings.php](https://gist.github.com/woogists/8da516cfc0391780393d9b1af0c0471d#file-wc-sanitize-settings-php) hosted with ❤ by [GitHub](https://github.com)

### [Validation](#section-9)

[↑ Back to top](#doc-title "Back to top")

Validation isn’t always necessary but it’s nice to do. If your API keys are always 10 characters long and someone enters one that’s not 10 then you can print out an error message and prevent the user a lot of headache when they assumed they put it in correctly. First set up a `validate_{setting key}_field` method for each field you want to validate. For example, with the `api_key` field you need a `validate_api_key_field()` method. .gist table { margin-bottom: 0; }

public function validate\_api\_key\_field( $key, $value ) {

if ( isset( $value ) && 20 < strlen( $value ) ) {

WC\_Admin\_Settings::add\_error( esc\_html\_\_( 'Looks like you made a mistake with the API Key field. Make sure it isn&apos;t longer than 20 characters', 'woocommerce-integration-demo' ) );

}

return $value;

}

[view raw](https://gist.github.com/woogists/52cccaf4c70620e6013a9bc0fceeeae2/raw/ea181e2d78e1ebb3ceeeae698f135bd6039d4a8d/wc-validate-settings.php) [wc-validate-settings.php](https://gist.github.com/woogists/52cccaf4c70620e6013a9bc0fceeeae2#file-wc-validate-settings-php) hosted with ❤ by [GitHub](https://github.com)

[A complete example](#section-10)
---------------------------------

[↑ Back to top](#doc-title "Back to top")

If you’ve been following along you should have a complete integration example. If you have any problems see our [full integration demo](https://github.com/woogists/woocommerce-integration-demo "Integration Demo").
