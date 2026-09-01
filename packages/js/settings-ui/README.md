# Settings UI

React utilities for WooCommerce settings pages that opt in to the settings UI renderer.

> **This package is experimental.** Until it is marked as stable, its API and the settings UI schema can change in backwards-incompatible ways in any release.

For the full integration guide, see [Settings UI](../../../docs/extensions/settings-and-config/settings-ui.md).

## Usage

PHP settings pages continue to register through `WC_Settings_Page`. A page opts in by returning a settings UI adapter from `get_settings_ui_page()`. The adapter provides the canonical schema, save adapter, and any script handles that must load before the React app mounts.

Custom JavaScript registers scoped components:

```ts
import { registerSettingsExtension } from '@woocommerce/settings-ui';
import { PaymentMethodPicker } from './payment-method-picker';

registerSettingsExtension( {
	scope: {
		page: 'my_plugin',
		section: 'payments',
	},
	components: {
		'my-plugin/payment-method-picker': PaymentMethodPicker,
	},
} );
```

Field schemas can then reference the registered component:

```php
array(
	'id'        => 'my_plugin_payment_methods',
	'type'      => 'multiselect',
	'title'     => 'Payment methods',
	'component' => 'my-plugin/payment-method-picker',
)
```

Use native fields when possible and register custom components only for fields that need plugin-specific UI.
