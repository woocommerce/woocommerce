---
post_title: Registering settings UI components
sidebar_label: Settings UI components
sidebar_position: 6
---

# Registering settings UI components

> **Experimental.** The Settings UI is behind the default-off `settings-ui` feature flag and its APIs, including the component contract described here, are subject to change while the feature matures.

Use custom components when a WooCommerce settings field needs plugin-specific React UI that cannot be represented by a supported field type.

For most fields, prefer the built-in renderer. Custom components are best for specialized selectors, previews, or validation flows.

Registered components are [DataForm](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/) edit controls: the same component contract used by WordPress DataForm everywhere else in the admin. A component written for the Settings UI works as a DataForm control and the other way round.

## PHP field metadata

Declare a stable component name on the field:

```php
array(
	'id'        => 'my_plugin_payment_methods',
	'title'     => __( 'Payment methods', 'my-plugin' ),
	'type'      => 'multiselect',
	'component' => 'my-plugin/payment-method-picker',
	'options'   => array(
		'card' => __( 'Card', 'my-plugin' ),
		'bnpl' => __( 'Buy now, pay later', 'my-plugin' ),
	),
)
```

The `component` value is a name, not a script handle. It lets the PHP schema say which renderer a field intends to use while JavaScript supplies the implementation.

## Register JavaScript components

Register components with `registerSettingsExtension()` from `@woocommerce/settings-ui`:

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

Registrations are scoped by settings page and, optionally, by section. This prevents one plugin from accidentally replacing another plugin's field behavior. Omit `section` for a page-wide registration, use `section: ''` for the default section only, or pass a section id such as `section: 'payments'` for one named section.

## Component props

Custom components receive the DataForm control props, re-exported as `SettingsEditControlProps`:

```ts
type SettingsEditControlProps = {
	// The current settings values, keyed by field id.
	data: Record< string, string | number | boolean | string[] | null >;
	// The normalized DataForm field. Use field.getValue( { item: data } )
	// to read the current value and field.setValue( { item, value } ) to
	// build a change record that preserves the saved value format.
	field: NormalizedField;
	// Takes a partial record of field ids to new values. Multi-field
	// writes are a single call.
	onChange: ( edits: Record< string, unknown > ) => void;
	hideLabelFromVision?: boolean;
	validity?: SettingsFieldValidity;
};
```

Page-level state is available through the `useSettingsUIContext()` hook:

```ts
import { useSettingsUIContext } from '@woocommerce/settings-ui';

const { schema, context, initialValues } = useSettingsUIContext();
```

Call `onChange()` with a record of field ids to next values. The settings UI handles hidden input serialization for the field's save adapter.

## Example component

```tsx
import type { SettingsEditControlProps } from '@woocommerce/settings-ui';

export const PaymentMethodPicker = ( {
	data,
	field,
	onChange,
}: SettingsEditControlProps ) => {
	const value = field.getValue( { item: data } );
	const selectedValues = Array.isArray( value ) ? value : [];

	return (
		<fieldset>
			<legend>{ field.label }</legend>
			{ field.elements?.map( ( option ) => {
				const checked = selectedValues.includes( option.value );

				return (
					<label key={ option.value }>
						<input
							type="checkbox"
							checked={ checked }
							onChange={ () => {
								onChange(
									field.setValue( {
										item: data,
										value: checked
											? selectedValues.filter(
													( item ) =>
														item !== option.value
											  )
											: [
													...selectedValues,
													option.value,
											  ],
									} )
								);
							} }
						/>
						{ option.label }
					</label>
				);
			} ) }
		</fieldset>
	);
};
```

## Field-specific overrides

If a legacy field cannot add `component` metadata directly, register a field override by field id:

```ts
registerSettingsExtension( {
	scope: {
		page: 'my_plugin',
	},
	fieldOverrides: {
		my_plugin_payment_methods: PaymentMethodPicker,
	},
} );
```

Field overrides are useful during migration, but component metadata is preferred because the intended renderer stays close to the PHP field schema.

## Type renderers

Use `typeRenderers` when every field of a type should share the same renderer within a page scope:

```ts
registerSettingsExtension( {
	scope: {
		page: 'my_plugin',
	},
	typeRenderers: {
		my_plugin_color: ColorField,
	},
} );
```

Resolution order is:

1. `field.component`
2. `fieldOverrides[ field.id ]`
3. `typeRenderers[ field.type ]`
4. The DataForm control for the field type

## Enqueue the component script

Register your script in WordPress and return its handle from the settings integration that owns the fields.

For a section registered under an existing tab, return the handle from the section object:

```php
<?php
use Automattic\WooCommerce\Admin\Settings\SettingsSection;

final class My_Plugin_Settings_Section extends SettingsSection {
	// Other settings section methods omitted for brevity.

	public function get_script_handles( WC_Settings_Page $parent_page ): array {
		return array( 'my-plugin-settings-ui' );
	}
}
```

For a full settings tab that opts in through a `WC_Settings_Page` adapter, return the handle from the adapter:

```php
<?php
use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;

final class My_Plugin_Settings_UI_Page extends LegacySettingsPageAdapter {
	public function get_script_handles( string $section ): array {
		return array( 'my-plugin-settings-ui' );
	}
}
```

WooCommerce loads the settings UI package first, then your script, then mounts the settings app.
