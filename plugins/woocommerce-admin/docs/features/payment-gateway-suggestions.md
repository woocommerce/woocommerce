# WooCommerce Payment Gateway Suggestions

This feature uses JSON to retrieve the currently recommended payment gateways. The feature is capable of polling remote JSON sources to retrieve a list of recommended and visible gateways based on rulesets to be shown to the user.

After merchants click on a recommendation, plugins from this source will then walk through an installer step, followed by a connection step with the minimum required fields for setup defined by the downloaded plugin.

### Quick start

Gateway suggestions are retrieved from a REST API and can be added via a remote JSON data source or filtered with the `woocommerce_admin_payment_gateway_suggestion_specs` filter.

To quickly get started with an example plugin, run the following:

`WC_EXT=payment-gateway-suggestions pnpm example --filter=@woocommerce/admin-library`

This will create a new plugin that when activated will add two new gateway suggestions.  The first is a simple gateway demonstrating how configuration fields can be pulled from the gateway class to create a configuration form.  The second gateway shows a more customized approach via SlotFill.

## Data Source Polling

If a store is opted into marketplace suggestions via `woocommerce_show_marketplace_suggestions` the suggestions by default will be retrieved from `https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/suggestions.json`.

If a user is not opted into marketplace suggestions or polling fails, the gateway suggestions will fall back to the defaults in the `DefaultPaymentGateways` class.

## Remote Data Source Schema

The data source schema defines the recommended payment gateways and required plugins to kick of the setup process. The goal of this config is to provide the minimum amount of information possible to show a list of gateways and allow the gateways themselves to define specifics around configuration.

```json
[
  {
    "key": "gateway-key",
    "title": "Gateway Example",
    "content": "Content to be displayed in the recommended payment gateway list.",
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

The specs use the [rule processor](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Admin/RemoteInboxNotifications#rule) to determine if a gateway should be shown using the `is_visible` property.

## Payment Gateway Configs

Information concerning the configuration and status of the payment gateway is determined by the payment gateway itself. This allows a single source of truth for this information, but more importantly allows the latest and most accurate settings to be included with the plugin downloaded from WordPress.org.

Additional information is added to the existing payment gateway in the WooCommerce REST API response. The following public methods can be added to the payment gateway class to pass information to the recommended payment gateways task:

| Name                                | Return  | Default | Description                                                                                                                                                                                                                                                                    |
| ----------------------------------- | ------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `needs_setup()`                     | boolean | `false` | Used to determine if the gateway still requires setup in order to be used.                                                                                                                                                                                                     |
| `get_required_settings_keys()`      | array   | `[]`    | An array of keys for fields required to properly configure the gateway. The keys must match those of already registered form fields in the payment gateway.                                                                                                                    |
| `get_connection_url()`              | string  | `null`  | The connection URL to be used to quickly connect a payment gateway provider. If provided, this will be used in place of required setting fields.                                                                                                                               |
| `get_post_install_script_handles()` | array   | `[]`    | An array of script handles previously registered with `wp_register_script` to enqueue after the payment gateway has been installed. This is primarily used to `SlotFill` the payment connection step, but can allow any script to be added to assist in payment gateway setup. |
| `get_setup_help_text()`             | string  | `null`  | Help text to be shown above the connection step's submit button.                                                                                                                                                                                                               |


## SlotFill

By default, the client will generate a payment gateway setup form from the settings fields registered in `get_required_settings_keys()`.  However, payment gateway tasks can be SlotFilled to provide custom experiences. This is useful if a gateway cannot follow the generic payment steps to be fully set up.

### WooPaymentGatewayConfigure

To customize the configuration form used in the payment setup, you can use [WooPaymentGatewayConfigure](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/onboarding/src/components/WooPaymentGatewayConfigure). 

This will leave the default gateway installation and stepper in place, but allow the form to be customized as needed.

### WooPaymentGatewaySetup

To completely override the stepper and default installation behavior, the gateway can be SlotFilled using [WooPaymentGatewaySetup](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/onboarding/src/components/WooPaymentGatewaySetup).

## Post install setup

Since plugin installation happens asynchronously, a full page reload will not occur between gateway installation and configuration. This renders functions like `wp_enqueue_script` ineffective.

To allow for interaction with the newly registered gateway and allow `SlotFill` to work on a newly installed plugin, the gateway can provide a URL to be loaded immediately after installation using `get_post_install_script_handles()`.  Registered scripts in this handler will automatically be injected into the page after the gateway has been installed.
