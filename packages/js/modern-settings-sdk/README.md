# Modern Settings SDK

React utilities for WooCommerce settings pages that opt in to the modern settings renderer.

## Usage

PHP settings pages continue to register through `WC_Settings_Page`. A page opts in by returning a modern settings adapter from `get_modern_settings_page()`. The adapter provides the canonical schema, save adapter, and any script handles that must load before the React app mounts.

Custom JavaScript registers scoped components:

```ts
import { registerSettingsExtension } from '@woocommerce/modern-settings-sdk';
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
