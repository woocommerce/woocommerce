---
post_title: Registering settings UI components
sidebar_label: Settings UI components
sidebar_position: 6
---

# Registering settings UI components

Use custom components when a WooCommerce settings field needs plugin-specific React UI that cannot be represented by a native field type.

For most fields, prefer the control supplied by DataForm. Custom components are best for specialized selectors, previews, or controls that DataForm does not provide.

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

Custom components receive stable field props:

```ts
type SettingsFieldComponentProps = {
	field: {
		id: string;
		label: string;
		type: string;
		description?: string;
		value?: string | number | boolean | string[] | null;
		options?: Array< { label: string; value: string } >;
		component?: string;
		placeholder?: string;
		disabled?: boolean;
		customAttributes?: Record< string, string | number | boolean >;
	};
	value: string | number | boolean | string[] | null;
	onChange: ( value: string | number | boolean | string[] | null ) => void;
	values: Record< string, string | number | boolean | string[] | null >;
	initialValues: Record< string, string | number | boolean | string[] | null >;
	setValue: (
		fieldId: string,
		value: string | number | boolean | string[] | null
	) => void;
	setValues: (
		values: Partial<
			Record< string, string | number | boolean | string[] | null >
		>
	) => void;
	context: {
		page: string;
		section?: string;
	};
};
```

Call `onChange()` with the next field value. Use `setValue()` or `setValues()` when one control updates related fields. The settings UI adapts these calls to DataForm and handles hidden input serialization for the field's save adapter.

## Example component

```tsx
import type { SettingsFieldComponentProps } from '@woocommerce/settings-ui';

export const PaymentMethodPicker = ( {
	field,
	value,
	onChange,
}: SettingsFieldComponentProps ) => {
	const selectedValues = Array.isArray( value ) ? value : [];

	return (
		<fieldset>
			<legend>{ field.label }</legend>
			{ field.options?.map( ( option ) => {
				const checked = selectedValues.includes( option.value );

				return (
					<label key={ option.value }>
						<input
							type="checkbox"
							checked={ checked }
							onChange={ () => {
								onChange(
									checked
										? selectedValues.filter(
												( item ) =>
													item !== option.value
										  )
										: [ ...selectedValues, option.value ]
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
4. DataForm's control for the field type

## Register custom validators

Add serializable validation metadata to the PHP field and register named validation logic in JavaScript. Validator names keep executable code out of the PHP schema.

```php
array(
	'id'         => 'my_plugin_account_id',
	'title'      => __( 'Account ID', 'my-plugin' ),
	'type'       => 'text',
	'validation' => array(
		'required'  => true,
		'validator' => 'my-plugin/account-id',
	),
)
```

```ts
registerSettingsExtension( {
	scope: {
		page: 'my_plugin',
	},
	validators: {
		'my-plugin/account-id': async ( { value } ) => {
			const isAvailable = await checkAccountId( String( value ) );
			return isAvailable ? null : 'This account ID is already in use.';
		},
	},
} );
```

Validators return `null` for a valid value or an error message for an invalid value. They can return the result directly or return a promise. DataForm displays the field error and the Settings UI prevents saving while validation is pending or invalid.

The validation callback also receives `field`, `values`, `initialValues`, `context`, and `schema`. PHP sanitisation and save filters remain the authoritative server-side validation layer.

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
