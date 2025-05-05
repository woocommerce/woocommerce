# Add a new inner block containing a custom field to the WooCommerce Checkout Block

This document describes how a developer can insert an input field into the Checkout block and have its value passed to the Store API so it's available when processing the checkout.

## Overview

Developers can extend the Checkout block to add new inner blocks and process additional data through the checkout POST request. This involves leveraging the extensibility interfaces provided by Gutenberg and WooCommerce Blocks. This is demonstrated in more detail in our tutorial: [Tutorial: Extending the WooCommerce Checkout Block
](https://developer.woocommerce.com/2023/08/07/extending-the-woocommerce-checkout-block-to-add-custom-shipping-options/).

## Prerequisites

- Basic understanding of React and the Gutenberg block editor.
- Familiarity with WooCommerce Blocks' extensibility interfaces and the Store API.

## Step-by-Step Guide

### 1. Set Up Your Development Environment

Ensure you have the following files in your project:

- `index.js`: Entry point for Webpack, imports, and registers the block type.
- `edit.js`: Handles the rendering of the block in the editor interface.
- `block.json`: Provides metadata and configurations for the block.
- `block.js`: Manages the block's state and user interactions.
- `frontend.js`: Registers the checkout block component for the frontend.

Refer to [this tutorial](https://developer.woocommerce.com/2023/08/07/extending-the-woocommerce-checkout-block-to-add-custom-shipping-options/) for an example of adding a custom shipping option to the checkout block.

### 2. Add a new field block to the Checkout Block

To add a field block to the Checkout Block you will need to add the following entries to the `block.json` file of your block:

```json
"parent": [ "woocommerce/checkout-shipping-methods-block" ],
"attributes": {
	"lock": {
		"type": "object",
		"default": {
			"remove": true,
			"move": true
		}
	}
}
```

- The [lock attribute](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-templates/#individual-block-locking) is an object that controls whether the block can be removed or moved. By default, the lock attribute is set to allow the block to be removed and moved. However, by modifying the lock attribute, you can “force” the block to be non-removable. For example, you can set both remove and move properties to false in order to prevent the block from being removed or moved.
- The [parent attribute](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#parent) specifies the parent block that this block should be nested within it. It determines where the block will render. In our example, the block is a child of the `woocommerce/checkout-shipping-methods-block`. This means that your block will be rendered within the `woocommerce/checkout-shipping-methods-block`. If the shipping methods block is not required, your block will not be rendered.

### 3. Setting custom checkout data

We can set the added field data to send it to the `wc/store/checkout` endpoint when processing orders using the function `setExtensionData`:

```JavaScript
setExtensionData(
	'namespace-of-your-block',
	'key-of-your-data',
	value
);
```

#### Parameters

- namespace `string` - The namespace of your block.
- key `string` - The key of your data.
- value `any` - The value of your data.

#### How it works

 1. `setExtensionData` is passed to inner blocks via props.
 2. It updates the `extensionData` key of the `wc/store/checkout` data store.
 3. This key is passed as part of the request body when POSTing to the checkout endpoint.

#### Code Example

```JavaScript
// block.js
export const Block = ( { checkoutExtensionData, extensions } ) => {
/**
	 * setExtensionData will update the wc/store/checkout data store with the values supplied. It
	 * can be used to pass data from the client to the server when submitting the checkout form.
	 */
	const { setExtensionData } = checkoutExtensionData;
}

// ... Some code here

useEffect( () => {
/**
	* This code should use `setExtensionData` to update the `key-of-your-data` key
	* in the `namespace-of-your-block` namespace of the checkout data store.
*/
setExtensionData(
	'namespace-of-your-block',
	'key-of-your-data',
	value
);
}, [ setExtensionData, value ] );
```

#### Screenshots

Screenshots of Redux Dev tool showing the data store before and after the setExtensionData call:

| Before | After |
| ------ | ----- |
|    <img width="713" alt="image" src="https://github.com/woocommerce/woocommerce-blocks/assets/14235870/948581f5-fdc2-4df1-963f-9aeb4b18b042">    |    <img width="723" alt="image" src="https://github.com/woocommerce/woocommerce-blocks/assets/14235870/ddc7dbe7-3fad-44cd-bd19-ce78bc49b951">   |

### 4. Processing the Checkout POST Request

To process the added field data, we'll need extend the Store API to tell it to expect additional data. See more details in the [Exposing your data in the Store API](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md)

#### Code Example

We will use the following PHP files in our example:

- The `custom-inner-block-blocks-integration.php` file: Enqueue scripts, styles, and data on the frontend when the Checkout blocks is being used. See more details in the [IntegrationInterface](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-block/integration-interface.md) documentation.

```php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class Custom_Inner_Block_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'new-field-block';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		// ... Some code here: (e.g. init functions that registers scripts and styles, and other instructions)
	}

	// ... Other functions here
}
```

- The `custom-inner-block-extend-store-endpoint.php` file: extends the [Store API](https://github.com/woocommerce/woocommerce-blocks/tree/trunk/src/StoreApi) and adds hooks to save and display your new field block instructions. This doesn't save the data from the custom block anywhere by default, but you can add your own logic to save the data to the database.

```php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

/**
 * Your New Field Block Extend Store API.
 */
class Custom_Inner_Block_Extend_Store_Endpoint {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendRestApi
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'new-field-block';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 */
	public static function init() {
		self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get( Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {
		if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
			self::$extend->register_endpoint_data(
				[
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'schema_callback' => [ 'Custom_Inner_Block_Extend_Store_Endpoint', 'extend_checkout_schema' ],
					'schema_type'     => ARRAY_A,
				]
			);
		}
	}

	/**
	 * Register the new field block schema into the Checkout endpoint.
	 *
	 * @return array Registered schema.
	 *
	 */
	public static function extend_checkout_schema() {
		return [
            'Value_1'   => [
                'description' => 'A description of the field',
                'type'        => 'string', // ... type of the field, this should be a string
                'context'     => [ 'view', 'edit' ], // ... context of the field, this should be an array containing 'view' and 'edit'
                'readonly'    => true, // ... whether the field is readonly or not, this should be a boolean
                'optional'    => true, // ... whether the field is optional or not, this should be a boolean
            ],
			// ... other values
        ];
	}
}
```

- The `new-field-block.php` file: the main plugin file that loads the `custom-inner-block-blocks-integration.php` and `custom-inner-block-extend-store-endpoint.php` files.

```php
<?php
/**
 * Plugin Name:     New Field Block
 * Version:         1.0
 * Author:          Your Name Here
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     new-field-block
 *
 * @package         create-block
 */

// ... Some code here

/**
 * Include the dependencies needed to instantiate the block.
 */
add_action(
	'woocommerce_blocks_loaded',
	function() {
		require_once __DIR__ . '/custom-inner-block-blocks-integration.php';
		require_once __DIR__ . '/custom-inner-block-blocks-integration.php';

		// Initialize our store endpoint extension when WC Blocks is loaded.
		Custom_Inner_Block_Extend_Store_Endpoint::init();

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new Custom_Inner_Block_Blocks_Integration() );
			}
		);
	}
);

// ... Some code here
```

Here is an example from our [tutorial](https://developer.woocommerce.com/2023/08/07/extending-the-woocommerce-checkout-block-to-add-custom-shipping-options/) of how to get this custom field's data while processing the checkout. This example is from the `shipping-workshop-blocks-integration.php` file. The complete code can be found in this [GitHub repository](https://github.com/woocommerce/wceu23-shipping-workshop-final/blob/main/shipping-workshop-blocks-integration.php#L42-L83).

```php
private function save_shipping_instructions() {
	/**
	 * We write a hook, using the `woocommerce_store_api_checkout_update_order_from_request` action
	 * that will update the order metadata with the shipping-workshop alternate shipping instruction.
	 *
	 * The documentation for this hook is at: https://github.com/woocommerce/woocommerce-blocks/blob/b73fbcacb68cabfafd7c3e7557cf962483451dc1/docs/third-party-developers/extensibility/hooks/actions.md#woocommerce_store_api_checkout_update_order_from_request
	 */
	add_action(
		'woocommerce_store_api_checkout_update_order_from_request',
		function( \WC_Order $order, \WP_REST_Request $request ) {
			$shipping_workshop_request_data = $request['extensions'][$this->get_name()];
			$alternate_shipping_instruction = $shipping_workshop_request_data['alternateShippingInstruction'];
			$other_shipping_value           = $shipping_workshop_request_data['otherShippingValue'];
			$order->update_meta_data( 'shipping_workshop_alternate_shipping_instruction', $alternate_shipping_instruction );
			$order->save();
		},
		10,
		2
	);
}
```

## Conclusion

By following the steps above, you can add and process new field blocks in the WooCommerce checkout block. For complete implementation and additional examples, refer to the provided [tutorial](https://developer.woocommerce.com/2023/08/07/extending-the-woocommerce-checkout-block-to-add-custom-shipping-options/) and the corresponding [GitHub repository](https://github.com/woocommerce/wceu23-shipping-workshop-final/).
