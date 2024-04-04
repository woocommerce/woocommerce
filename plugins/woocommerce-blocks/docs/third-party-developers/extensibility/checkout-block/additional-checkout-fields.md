# Additional Checkout Fields  <!-- omit in toc -->

## Table of Contents  <!-- omit in toc -->

- [Available field locations](#available-field-locations)
    - [Contact information](#contact-information)
    - [Address](#address)
    - [Additional information](#additional-information)
- [Supported field types](#supported-field-types)
- [Using the API](#using-the-api)
    - [Options](#options)
        - [General options](#general-options)
        - [Options for `text` fields](#options-for-text-fields)
        - [Options for `select` fields](#options-for-select-fields)
        - [Options for `checkbox` fields](#options-for-checkbox-fields)
    - [Attributes](#attributes)
- [Usage examples](#usage-examples)
    - [Rendering a text field](#rendering-a-text-field)
    - [Rendering a checkbox field](#rendering-a-checkbox-field)
    - [Rendering a select field](#rendering-a-select-field)
    - [The select input before being focused](#the-select-input-before-being-focused)
    - [The select input when focused](#the-select-input-when-focused)
- [Validation and sanitization](#validation-and-sanitization)
    - [Sanitization](#sanitization)
        - [Using the `_experimental_woocommerce_blocks_sanitize_additional_field` filter](#using-the-_experimental_woocommerce_blocks_sanitize_additional_field-filter)
            - [Example of sanitization](#example-of-sanitization)
    - [Validation](#validation)
        - [Single field validation](#single-field-validation)
            - [Using the `__experimental_woocommerce_blocks_validate_additional_field` action](#using-the-__experimental_woocommerce_blocks_validate_additional_field-action)
                - [The `WP_Error` object](#the-wp_error-object)
                - [Example of single-field validation](#example-of-single-field-validation)
        - [Multiple field validation](#multiple-field-validation)
            - [Using the `__experimental_woocommerce_blocks_validate_location_{location}_fields` action](#using-the-__experimental_woocommerce_blocks_validate_location_location_fields-action)
            - [Example of location validation](#example-of-location-validation)
- [A full example](#a-full-example)

A common use-case for developers and merchants is to add a new field to the Checkout form to collect additional data about a customer or their order.

This document will outline the steps an extension should take to register some additional checkout fields.

> [!NOTE]
> Additional Checkout fields is still in the testing phases, use it to test the API and leave feedback in this [public discussion.](https://github.com/woocommerce/woocommerce/discussions/42995)

## Available field locations

Additional checkout fields can be registered in three different places:

- Contact information
- Addresses (Shipping **and** Billing)
- Additional information

A field can only be shown in one location, it is not possible to render the same field in multiple locations in the same registration.

### Contact information

The contact information section currently renders at the top of the form. It contains the `email` field and any other additional fields.

![Showing the contact information section with two fields rendered, email and an additional checkout field (optional)](https://github.com/woocommerce/woocommerce/assets/5656702/097c2596-c629-4eab-9604-577ee7a14cfe)

Fields rendered here will be saved to the shopper's account. They will be visible and editable render in the shopper's "Account details" section.

### Address

The "Address" section currently contains a form for the shipping address and the billing address. Additional checkout fields can be registered to appear within these forms.

![The shipping address form showing the additional checkout field at the bottom](https://github.com/woocommerce/woocommerce/assets/5656702/746d280f-3354-4d37-a78a-a2518eb0e5de)

Fields registered here will be saved to both the customer _and_ the order, so returning customers won't need to refill those values again.

If a field is registered in the `address` location it will appear in both the shipping **and** the billing address. It is not possible to have the field in only one of the addresses.

You will also end up collecting two values for this field, one for shipping and one for billing.

### Additional information

As part of the additional checkout fields feature, the checkout block has a new inner block called the "Additional information block".

This block is used to render fields that aren't part of the contact information or address information, for example it may be a "How did you hear about us" field or a "Gift message" field.

Fields rendered here will be saved to the order. They will not be part of the customer's saved address or account information. New orders will not have any previously used values pre-filled.

![The additional order information section containing an additional checkout field](https://github.com/woocommerce/woocommerce/assets/5656702/295b3048-a22a-4225-96b0-6b0371a7cd5f)

By default, this block will render as the last step in the Checkout form, however it can be moved using the Gutenberg block controls in the editor.

![The additional order information block in the post editor"](https://github.com/woocommerce/woocommerce/assets/5656702/05a3d7d9-b3af-4445-9318-443ae2c4d7d8)

## Supported field types

The following field types are supported:

- `select`
- `text`
- `checkbox`

There are plans to expand this list, but for now these are the types available.

## Using the API

To register additional checkout fields you must use the `__experimental_woocommerce_blocks_register_checkout_field` function.

It is recommended to run this function after the `woocommerce_blocks_loaded` action.

The registration function takes an array of options describing your field. Some field types take additional options.

### Options

#### General options

These options apply to all field types (except in a few circumstances which are noted inline).

| Option name         | Description                                                                                                                         | Required? | Example                                      | Default value                                                                                                                                                                                                                                                                                  |
|---------------------|-------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`                | The field's ID. This should be a unique identifier for your field. It is composed of a namespace and field name separated by a `/`. | Yes       | `plugin-namespace/how-did-you-hear`          | No default - this must be provided.                                                                                                                                                                                                                                                            |
| `label`             | The label shown on your field. This will be the placeholder too.                                                                    | Yes       | `How did you hear about us?`                 | No default - this must be provided.                                                                                                                                                                                                                                                            |
| `optionalLabel`     | The label shown on your field if it is optional. This will be the placeholder too.                                                  | No        | `How did you hear about us? (Optional)`      | The default value will be the value of `label` with `(optional)` appended.                                                                                                                                                                                                                     |
| `location`          | The location to render your field.                                                                                                  | Yes       | `contact`, `address`, or `additional`        | No default - this must be provided.                                                                                                                                                                                                                                                            |
| `type`              | The type of field you're rendering. It defaults to `text` and must match one of the supported field types.                          | No        | `text`, `select`, or `checkbox`              | `text`                                                                                                                                                                                                                                                                                         |
| `attributes`        | An array of additional attributes to render on the field's input element. This is _not_ supported for `select` fields.              | No        | `[	'data-custom-data' => 'my-custom-data' ]` | `[]`                                                                                                                                                                                                                                                                                           |
| `sanitize_callback` | A function called to sanitize the customer provided value when posted.                                                              | No        | See example below                            | By default the field's value is returned unchanged.                                                                                                                                                                                                                          |
| `validate_callback` | A function called to validate the customer provided value when posted. This runs _after_ sanitization.                              | No        | See example below                            | The default validation function will add an error to the response if the field is required and does not have a value. [See the default validation function.](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Blocks/Domain/Services/CheckoutFields.php#L270-L281) |

##### &ast; Example of `sanitize_callback`. This function will remove spaces from the value

```php
'sanitize_callback' => function( $field_value ) {
	return str_replace( ' ', '', $field_value );
},
```

##### &ast; Example of `validate_callback`. This function will check if the value is an email

```php
'validate_callback' => function( $field_value ) {
	if ( ! is_email( $field_value ) ) {
		return new WP_Error( 'invalid_alt_email', 'Please ensure your alternative email matches the correct format.' );
	}
},
```

#### Options for `text` fields

As well as the options above, text fields also support a `required` option. If this is `true` then the shopper _must_ provide a value for this field during the checkout process.

| Option name     | Description                                                                                                                         | Required? | Example                                      | Default value |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------|-----------|----------------------------------------------|---|
| `required` | If this is `true` then the shopper _must_ provide a value for this field during the checkout process. | No | `true` | `false` |

#### Options for `select` fields

As well as the options above, select fields must also be registered with an  `options` option. This is used to specify what options the shopper can select.

Select fields can also be marked as required. If they are not (i.e. they are optional), then an empty entry will be added to allow the shopper to unset the field.

| Option name | Description | Required? | Example        | Default value |
|-----|-----|-----|----------------|--------------|
| `options` | An array of options to show in the select input. Each options must be an array containing a `label` and `value` property. Each entry must have a unique `value`. Any duplicate options will be removed. The `value` is what gets submitted to the server during checkout and the `label` is simply a user-friendly representation of this value. It is not transmitted to the server in any way. | Yes | &ast;see below | No default - this must be provided. |
| `required` | If this is `true` then the shopper _must_ provide a value for this field during the checkout process. | No | `true` | `false` |

##### &ast;Example of `options` value

```php
[

	[
		'value' => 'store_1',
		'label' => 'Our London Store'
	],
	[
		'value' => 'store_2',
		'label' => 'Our Paris Store'
	],
	[
		'value' => 'store_3',
		'label' => 'Our New York Store'
	]
]
````

#### Options for `checkbox` fields

The checkbox field type does not have any specific options, however `required` will always be `false` for a checkbox field. Making a checkbox field required is not supported.

### Attributes

Adding additional attributes to checkbox and text fields is supported. Adding them to select fields is **not possible for now**.

These attributes have a 1:1 mapping to the HTML attributes on `input` elements (except `pattern` on checkbox).

The supported attributes are:

- `data-*` attributes
- `aria-*` attributes
- `autocomplete`
- `autocapitalize`
- `pattern` (not supported on checkbox fields)
- `title`
- `maxLength` (equivalent to `maxlength` HTML attribute)
- `readOnly` (equivalent to `readonly` HTML attribute)

`maxLength` and `readOnly` are in camelCase because the attributes are rendered on a React element which must receive them in this format.

Certain attributes are not passed through to the field intentionally, these are `autofocus` and `disabled`. We are welcome to hear feedback and adjust this behaviour if valid use cases are provided.

## Usage examples

### Rendering a text field

This example demonstrates rendering a text field in the address section:

```php
add_action(
	'woocommerce_blocks_loaded',
	function() {
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'            => 'namespace/gov-id',
				'label'         => 'Government ID',
				'optionalLabel' => 'Government ID (optional)',
				'location'      => 'address',
				'required'      => true,
				'attributes'    => array(
					'autocomplete'     => 'government-id',
					'aria-describedby' => 'some-element',
					'aria-label'       => 'custom aria label',
					'pattern'          => '[A-Z0-9]{5}', // A 5-character string of capital letters and numbers.
					'title'            => 'Title to show on hover',
					'data-custom'      => 'custom data',
				),
			),
		);
	}
);
```

This results in the following address form (the billing form will be the same):

![The shipping address form with the Government ID field rendered at the bottom](https://github.com/woocommerce/woocommerce/assets/5656702/f6eb3c6f-9178-4978-8e74-e6b2ea353192)

The rendered markup looks like this:

```html
<input type="text" id="shipping-namespace/gov-id" autocapitalize="off"
       autocomplete="government-id" aria-label="custom aria label"
       aria-describedby="some-element" required="" aria-invalid="true"
       title="Title to show on hover" pattern="[A-Z0-9]{5}"
       data-custom="custom data" value="" >
```

### Rendering a checkbox field

This example demonstrates rendering a checkbox field in the contact information section:

```php
add_action(
	'woocommerce_blocks_loaded',
	function() {
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'namespace/marketing-opt-in',
				'label'    => 'Do you want to subscribe to our newsletter?',
				'location' => 'contact',
				'type'     => 'checkbox',
			)
		);
	}
);
````

This results in the following contact information section:

![The contact information section with a newsletter subscription checkbox rendered inside it](https://github.com/woocommerce/woocommerce/assets/5656702/7444e41a-97cc-451d-b2c9-4eedfbe05724)

Note that because an `optionalLabel` was not supplied, the string `(optional)` is appended to the label. To remove that an `optionalLabel` property should be supplied to override this.

### Rendering a select field

This example demonstrates rendering a select field in the additional information section:

```php
add_action(
	'woocommerce_blocks_loaded',
	function() {
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'namespace/how-did-you-hear-about-us',
				'label'    => 'How did you hear about us?',
				'location' => 'additional',
				'type'     => 'select',
				'options'  => [
					[
						'value' => 'google',
						'label' => 'Google'
					],
					[
						'value' => 'facebook',
						'label' => 'Facebook'
					],
					[
						'value' => 'friend',
						'label' => 'From a friend'
					],
					[
						'value' => 'other',
						'label' => 'Other'
					],
				]
			)
		);
	}
);
```

This results in the additional information section being rendered like so:

### The select input before being focused

![The select input before being focused](https://github.com/woocommerce/woocommerce/assets/5656702/bbe17ad0-7c7d-419a-951d-315f56f8898a)

### The select input when focused

![The select input when focused](https://github.com/woocommerce/woocommerce/assets/5656702/bd943906-621b-404f-aa84-b951323e25d3)

If it is undesirable to force the shopper to select a value, providing a value such as "None of the above" may help.

## Validation and sanitization

It is possible to add custom validation and sanitization for additional checkout fields using WordPress action hooks.

These actions happen in two places:

1. Updating and submitting the form during the checkout process and,
2. Updating address/contact information in the "My account" area.

### Sanitization

Sanitization is used to ensure the value of a field is in a specific format. An example is when taking a government ID, you may want to format it so that all letters are capitalized and there are no spaces. At this point, the value should **not** be checked for _validity_. That will come later. This step is only intended to set the field up for validation.

#### Using the `_experimental_woocommerce_blocks_sanitize_additional_field` filter

To run a custom sanitization function for a field you can use the `sanitize_callback` function on registration, or the `__experimental_woocommerce_blocks_sanitize_additional_field` filter.

| Argument     | Type              | Description                                                             |
|--------------|-------------------|-------------------------------------------------------------------------|
| `$field_value` | `boolean\|string` | The value of the field.                                                 |
| `$field_key`   | `string` | The ID of the field. This is the same ID the field was registered with. |

##### Example of sanitization

This example shows how to remove whitespace and capitalize all letters in the example Government ID field we added above.

```php
add_action(
	'_experimental_woocommerce_blocks_sanitize_additional_field',
	function ( $field_value, $field_key ) {
		if ( 'namespace/gov-id' === $field_key ) {
			$field_value = str_replace( ' ', '', $field_key );
			$field_value = strtoupper( $field_value );
		}
		return $field_value;
	},
	10,
	2
);
```

### Validation

There are two phases of validation in the additional checkout fields system. The first is validating a single field based on its key and value.

#### Single field validation

##### Using the `__experimental_woocommerce_blocks_validate_additional_field` action

When the `__experimental_woocommerce_blocks_validate_additional_field` action is fired  the callback receives the field's key, the field's value, and a `WP_Error` object.

To add validation errors to the response, use the [`WP_Error::add`](https://developer.wordpress.org/reference/classes/wp_error/add/) method.

| Argument     | Type              | Description                                                                                                                                                                           |
|--------------|-------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$errors`      | `WP_Error`        | An error object containing errors that were already encountered while processing the request. If no errors were added yet, it will still be a `WP_Error` object but it will be empty. |
| `$field_key`   | `string`          | The id of the field. This is the ID the field was registered with.                                                                                                                    |
| `$field_value` | `boolean\|string` | The value of the field                                                                                                                                                                |

###### The `WP_Error` object

When adding your error to the `WP_Error` object, it should have a unique error code. You may want to prefix the error code with the plugin namespace to reduce the chance of collision. Using codes that are already in use across other plugins may result in the error message being overwritten or showing in a different location.

###### Example of single-field validation

The below example shows how to apply custom validation to the `namespace/gov-id` text field from above. The code here ensures the field is made up of 5 characters, either upper-case letters or numbers. The sanitization function from the example above ensures that all whitespace is removed and all letters are capitalized, so this check is an extra safety net to ensure the input matches the pattern.

```php
add_action(
'__experimental_woocommerce_blocks_validate_additional_field',
	function ( WP_Error $errors, $field_key, $field_value ) {
		if ( 'namespace/gov-id' === $field_key ) {
			$match = preg_match( '/[A-Z0-9]{5}/', $field_value );
			if ( 0 === $match || false === $match ) {
				$errors->add( 'invalid_gov_id', 'Please ensure your government ID matches the correct format.' );
			}
		}
	},
	10,
	3
);
```

It is important to note that this action must _add_ errors to the `WP_Error` object it receives. Returning a new `WP_Error` object or any other value will result in the errors not showing.

If no validation errors are encountered the function can just return void.

#### Multiple field validation

There are cases where the validity of a field depends on the value of another field, for example validating the format of a government ID based on what country the shopper is in. In this case, validating only single fields (as above) is not sufficient as the country may be unknown during the `__experimental_woocommerce_blocks_validate_additional_field` action.

To solve this, it is possible to validate a field in the context of the location it renders in. The other fields in that location will be passed to this action.

##### Using the `__experimental_woocommerce_blocks_validate_location_{location}_fields` action

This action will be fired for each location that additional fields can render in (`address`, `contact`, and `additional`). For `address` it fires twice, once for the billing address and once for the shipping address.

The callback receives the keys and values of the other additional fields in the same location.

It is important to note that any fields rendered in other locations will not be passed to this action, however it might be possible to get those values by accessing the customer or order object, however this is not supported and there are no guarantees regarding backward compatibility in future versions.

| Argument | Type                        | Description                                                                                                                                                                           |
|----------|-----------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$errors`  | `WP_Error`                  | An error object containing errors that were already encountered while processing the request. If no errors were added yet, it will still be a `WP_Error` object but it will be empty. |
| `$fields`  | `array`                     | The fields rendered in this locations.                                                                                                                                                |
| `$group`   | `'billing'\|'shipping'\|''` | If the action is for the address location, the type of address will be set here. If it is for contact or addiotional, this will be an empty string.                                   |

There are several places where these hooks are fired.

- When checking out using the Checkout block or Store API.
    - `__experimental_woocommerce_blocks_validate_location_address_fields` (x2)
    - `__experimental_woocommerce_blocks_validate_location_contact_fields`
    - `__experimental_woocommerce_blocks_validate_location_additional_fields`
- When updating addresses in the "My account" area
    - `__experimental_woocommerce_blocks_validate_location_address_fields` (**x1** - only the address being edited)
- When updating the "Account details" section in the "My account" area
    - `__experimental_woocommerce_blocks_validate_location_contact_fields`

##### Example of location validation

In this example, assume there is another field registered alongside the `namespace/gov-id` called `namespace/confirm-gov-id`. This field will be a confirmation for the Government ID field.

The example below illustrates how to verify that the value of the confirmation field matches the value of the main field.

```php
add_action(
	'__experimental_woocommerce_blocks_validate_location_address_fields',
	function ( \WP_Error $errors, $fields, $group ) {
		if ( $fields['namespace/gov-id'] !== $fields['namespace/confirm-gov-id'] ) {
			$errors->add( 'gov_id_mismatch', 'Please ensure your government ID matches the confirmation.' );
		}
	},
	10,
	3
);
```

If these fields were rendered in the "contact" location instead, the code would be the same except the hook used would be: `__experimental_woocommerce_blocks_validate_location_contact_fields`.

## A full example

In this full example we will register the Government ID text field and verify that it conforms to a specific pattern.

This example is just a combined version of the examples shared above.

```php
add_action(
	'woocommerce_blocks_loaded',
	function() {
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'            => 'namespace/gov-id',
				'label'         => 'Government ID',
				'location'      => 'address',
				'required'      => true,
				'attributes'    => array(
					'autocomplete' => 'government-id',
					'pattern'      => '[A-Z0-9]{5}', // A 5-character string of capital letters and numbers.
					'title'        => 'Your 5-digit Government ID',
				),
			),
		);
		__experimental_woocommerce_blocks_register_checkout_field(
			array(
				'id'            => 'namespace/confirm-gov-id',
				'label'         => 'Confirm government ID',
				'location'      => 'address',
				'required'      => true,
				'attributes'    => array(
					'autocomplete' => 'government-id',
					'pattern'      => '[A-Z0-9]{5}', // A 5-character string of capital letters and numbers.
					'title'        => 'Confirm your 5-digit Government ID',
				),
			),
		);

		add_action(
			'_experimental_woocommerce_blocks_sanitize_additional_field',
			function ( $field_value, $field_key ) {
				if ( 'namespace/gov-id' === $field_key || 'namespace/confirm-gov-id' === $field_key ) {
					$field_value = str_replace( ' ', '', $field_key );
					$field_value = strtoupper( $field_value );
				}
				return $field_value;
			},
			10,
			2
		);

		add_action(
		'__experimental_woocommerce_blocks_validate_additional_field',
			function ( WP_Error $errors, $field_key, $field_value ) {
				if ( 'namespace/gov-id' === $field_key ) {
					$match = preg_match( '/[A-Z0-9]{5}/', $field_value );
					if ( 0 === $match || false === $match ) {
						$errors->add( 'invalid_gov_id', 'Please ensure your government ID matches the correct format.' );
					}
				}
				return $error;
			},
			10,
			3
		);
	}
);

add_action(
	'__experimental_woocommerce_blocks_validate_location_address_fields',
	function ( \WP_Error $errors, $fields, $group ) {
		if ( $fields['namespace/gov-id'] !== $fields['namespace/confirm-gov-id'] ) {
			$errors->add( 'gov_id_mismatch', 'Please ensure your government ID matches the confirmation.' );
		}
	},
	10,
	3
);
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&projects=&template=suggestion-for-documentation-improvement-correction.md&title=%5BDOC-BUG%5D%20./docs/third-party-developers/extensibility/checkout-block/available-filters.md)

<!-- /FEEDBACK -->
