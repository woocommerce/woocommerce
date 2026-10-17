Settings
=======

The settings used to modify the way data is retrieved or displayed in WooCommerce reports.

## Extending Settings

Settings can be added, removed, or modified outside oc `wc-admin` by hooking into `woocommerce_admin_analytics_settings`.  For example:

```js
addFilter( 'woocommerce_admin_analytics_settings', 'wc-example/my-setting', settings => {
	return [
		...settings,
		{
            name: 'custom_setting',
            label: __( 'Custom setting:', 'woocommerce' ),
            inputType: 'text',
            helpText: __( 'Help text to describe what the setting does.' ),
            defaultValue: 'Default value',
		},
	];
} );
```

Each settings has the following properties:

- `name` (string): The slug of the setting to be updated.
- `label` (string): The label used to describe and displayed next to the setting.
- `inputType` (enum: text|checkbox|checkboxGroup): The type of input to use.
- `helpText` (string): Text displayed beneath the setting.
- `options` (array): Array of options used for inputs with selectable options.
- `defaultValue` (string|array): Value used when resetting to default settings.
