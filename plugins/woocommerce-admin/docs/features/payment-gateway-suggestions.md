# WooCommerce Payment Gateway Suggestions

This feature uses JSON to retrieve the currently recommended payment gateways. The feature is capable of polling remote JSON sources to retrieve a list of recommended and visible gateways based on rulesets to be shown to the user.

After merchants click on a recommendation, plugins from this source will then walk through an installer step, followed by a connection step with the minimum required fields for setup defined by the downloaded plugin.

## Enabling Payment Gateway Suggestions

This feature is behind a feature flag. In order for it to run, the `payment-gateway-suggestions` must be set to `true` in `~/config/{environment}.json` and the plugin must be rebuilt either using `npm start` or `npm run build`.

Currently there is no working remote data source. For testing purposes, [this plugin](https://github.com/joshuatf/woocommerce-admin-remote-tester) can be used which adds a data source and removes the transient cache so the data source is re-fetched on each subsequent page load.

## Remote Data Source Schema

The data source schema defines the recommended payment gateways and required plugins to kick of the setup process. The goal of this config is to provide the mininum amount of information possible to show a list of gateways and allow the gateways themselves to define specifics around configuration.

```json
[
  {
    "key": "gateway-key",
    "locales": [
      {
        "locale": "en_US",
        "title": "Gateway Example",
        "content": "Content to be displayed in the recommended payment gateway list."
      }
    ],
    "image": "https://paymentgateway.com/path/to/image.png",
    "plugins": ["wp-org-plugin-slug"],
	"is_visible": [
		<Rule>,
		...
	]
  }
  ...
]
```

The specs use the [rule processor](https://github.com/woocommerce/woocommerce-admin/blob/main/src/RemoteInboxNotifications/README.md#rule) to determine if a gateway should be shown using the `is_visible` property.

## Payment Gateway Configs

Information concerning the configuration and status of the payment gateway is determined by the payment gateway itself. This allows a single source of truth for this information, but more importantly allows the latest and most accurate settings to be included with the plugin downloaded from WordPress.org.

Additional information is added to the existing payment gateway in the WooCommerce REST API response. The following public methods can be added to the payment gateway class to pass information to the recommended payment gateways task:

Name | Return | Default | Description
--- | --- | --- | ---
`needs_setup()` | boolean | `false` | Used to determine if the gateway still requires setup in order to be used.
`get_required_settings_keys()` | array | `[]` | An array of keys for fields required to properly configure the gateway.  The keys must match those of already registered form fields in the payment gateway.
`get_connection_url()` | string | `null` | The connection URL to be used to quickly connect a payment gateway provider. If provided, this will be used in place of required setting fields. 
`get_post_install_script_handles()` | array | `[]` | An array of script handles previously registered with `wp_register_script` to enqueue after the payment gateway has been installed.  This is primarily used to `SlotFill` the payment connection step, but can allow any script to be added to assist in payment gateway setup.
`get_setup_help_text()` | string | `null` | Help text to be shown above the connection step's submit button.

## SlotFill

Payment gateway tasks can be SlotFilled to provide custom experiences. This is useful if a gateway cannot follow the generic payment steps to be fully set up.

The entire payment gateway card can be SlotFilled using [WooPaymentGatewaySetup](https://github.com/woocommerce/woocommerce-admin/tree/main/packages/tasks/src/WooPaymentGatewaySetup) or simply SlotFill [WooPaymentGatewayConnect](https://github.com/woocommerce/woocommerce-admin/tree/main/packages/tasks/src/WooPaymentGatewayConnect) to leave the default installation and stepper in place.

Note that since plugin installation happens asynchronously, a full page reload will not occur between gateway installation and configuration.  This renders functions like `wp_enqueue_script` ineffective.  To solve this issue and allow `SlotFill` to work on a newly installed plugin, the gateway can provide a URL to be loaded immediately after installation using `get_post_install_script_handles()`.
