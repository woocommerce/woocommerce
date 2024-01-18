# Additional Checkout Fields

A common use-case for developers and merchants is to add a new field to the Checkout form to collect additional data about a customer or their order.

This document will outline the steps an extension should take to register some additional checkout fields.

## Available field locations

Additional checkout fields can be registered in three different places:

- Contact information
- Addresses (Shipping **and** Billing)
- Additional information

### Contact information

The contact information section currently renders at the top of the form. It contains the `email` field and any other additional fields.

<img width="715" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/097c2596-c629-4eab-9604-577ee7a14cfe">

Fields rendered here will be saved to the _order_ (i.e. they will not be part of the customer's saved address or account information. New orders will not have any previously used values pre-filled).

### Address

The address section currently contains a form for the shipping address and the billing address. Additional checkout fields can be registered here and they will appear within that form.

<img width="707" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/746d280f-3354-4d37-a78a-a2518eb0e5de">

If a field is registered in the `address` location it will appear in both the shipping **and** the billing address. It is not possible to have the field in only one of the addresses.

### Additional information

As part of the additional checkout fields feature, the checkout block has a new inner block called the "Additional information block".

This block is used to render fields that aren't part of the contact information or address information, for example it may be a "How did you hear about us" field or a "Gift message" field.

<img width="724" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/295b3048-a22a-4225-96b0-6b0371a7cd5f">

By default, this block will render as the last step in the Checkout form, however it can be moved using the Gutenberg block controls in the editor.

<img width="892" alt="Screenshot 2024-01-16 at 14 02 00" src="https://github.com/woocommerce/woocommerce/assets/5656702/05a3d7d9-b3af-4445-9318-443ae2c4d7d8">

## Supported field types

The following field types are supported:

- `select`
- `text`
- `checkbox`

There are plans to expand this list, but for now these are the types available.

## Using the API

To register additional checkout fields you must use the `woocommerce_blocks_register_checkout_field` function.

It is recommended to run this function after the `woocommerce_loaded` action.

The registration function takes an array of options describing your field. Some field types take additional options.

### Options

#### General options

These options apply to all field types (except in a few circumstances which are noted inline).

| Option name     | Description                                                                                                                            | Required? | Example                                      | Default value                                                              |
|---------------|----------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|-----------|----------------------------------------------------------------------------|
| `id`            | The field's ID. This should be a unique identifier for your field. It is composed of a namespace and field name separated by a `/`.    | Yes       | `plugin-namespace/how-did-you-hear`          | No default - this must be provided.                                        |
| `label`         | The label shown on your field. This will be the placeholder too.                                                                       | Yes       | `How did you hear about us?`                 | No default - this must be provided.                                        |
| `optionalLabel` | The label shown on your field if it is optional. This will be the placeholder too.                                                     | No        | `How did you hear about us? (Optional)`      | The default value will be the value of `label` with `(optional)` appended. |
| `location`      | The location to render your field.                                                                                                     | Yes       | `contact`, `address`, or `additional`        | No default - this must be provided.                                        |
| `type`          | The type of field you're rendering. It defaults to `text` and must match one of the supported field types.                             | No        | `text`, `select`, or `checkbox`              | `text`                                                                     |
| `attributes`    | An array of additional attributes to render on the field's input element. This is _not_ supported for `select` fields.                 | No        | `[	'data-custom-data' => 'my-custom-data' ]` | `[]`                                                                       |

#### Options for `text` fields

As well as the options above, text fields also support a `required` option. If this is `true` then the shopper _must_ provide a value for this field during the checkout process.

| Option name     | Description                                                                                                                         | Required? | Example                                      | Default value |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------|-----------|----------------------------------------------|---|
| `required` | If this is `true` then the shopper _must_ provide a value for this field during the checkout process. | No | `true` | `false` |

#### Options for `select` fields

As well as the options above, select fields must also be registered with an  `options` option. This is used to specify what options the shopper can select.

The `optionalLabel` option will never be shown as select fields are _always_ required.

<table>
	<tr>
		<th>Option name</th>
		<th>Description</th>
		<th>Required?</th>
		<th>Example</th>
		<th>Default value</th>
	</tr>
	<tr>
		<td>

`options`

</td>
		<td>

An array of options to show in the select input. Each options must be an array containing a `label` and `value` property. Each entry must have a unique `value`. Any duplicate options will be removed. The `value` is what gets submitted to the server during checkout and the `label` is simply a user-friendly representation of this value. It is not transmitted to the server in any way.</td>
		<td>Yes</td>
		<td>

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

</td>
		<td>
			No default - this must be provided.
		</td>
	</tr>
</table>

#### Options for `checkbox` fields

The checkbox field type does not have any specific options, however `required` will always be `false` for a checkbox field. Making a checkbox field required is not supported.

### Attributes

Adding additional attributes to checkbox and text fields is supported. Adding them to select fields is **not**.

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

## Usage examples

### Rendering a text field

This example demonstrates rendering a text field in the address section:

```php
add_action(
	'woocommerce_loaded',
	function() {
		woocommerce_blocks_register_checkout_field(
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

<img width="696" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/f6eb3c6f-9178-4978-8e74-e6b2ea353192">

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
	'woocommerce_loaded',
	function() {
		woocommerce_blocks_register_checkout_field(
			array(
				'id'       => 'namespace/marketing-opt-in',
				'label'    => 'Do you want to subscribe to our newsletter?',
				'location' => 'contact',
				'type'     => 'checkbox',
			)
		);
	}
);
```

This results in the following contact information section:

<img width="721" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/7444e41a-97cc-451d-b2c9-4eedfbe05724">

Note that because an `optionalLabel` was not supplied, the string `(optional)` is appended to the label. To remove that an `optionalLabel` property should be supplied to override this.

### Rendering a select field.

This example demonstrates rendering a select field in the additional information section:

```php
add_action(
	'woocommerce_loaded',
	function() {
		woocommerce_blocks_register_checkout_field(
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

<p>
	<img width="701" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/bbe17ad0-7c7d-419a-951d-315f56f8898a"><br />
	<em>The select input before being focused</em>
</p>

<p>
	<img width="724" alt="image" src="https://github.com/woocommerce/woocommerce/assets/5656702/bd943906-621b-404f-aa84-b951323e25d3"><br />
	<em>The select input when focused</em>
</p>

If it is undesirable to force the shopper to select a value, providing a value such as "None of the above" may help.

## Store API

Registered fields appear in the Store API schema. Making an OPTIONS request to the store's checkout endpoint will provide information on the expected checkout payload. Included in this will be any additional checkout fields. Fields added to the `address` location will be part of the `billing_address` and `shipping_address` properties, while fields added to the `additional` or `contact` locations will be within an `additional_fields` property. This property is at the top level (the same level as `billing_address` and `shipping_address`).

### POST example

Using the example fields registered above (Government ID, Do you want to subscribe to our newsletter?, and How did you hear about us?) a POST request to the checkout endpoint would look like this:

```json
{
	"shipping_address": {
		"first_name": "John",
		"last_name": "Doe",
		"company": "",
		"address_1": "30 Test Road",
		"address_2": "",
		"city": "Testville",
		"state": "CA",
		"postcode": "90210",
		"country": "US",
		"phone": "",
		"namespace/gov-id": "12345"
	},
	"billing_address": {
		"first_name": "Jon",
		"last_name": "Doe",
		"company": "",
		"address_1": "30 Test road",
		"address_2": "",
		"city": "Testville",
		"state": "CA",
		"postcode": "90210",
		"country": "US",
		"email": "test@mail.com",
		"phone": "",
		"namespace/gov-id": "12345"
	},
	"additional_fields": {
		"namespace/how-did-you-hear-about-us": "other"
	},
	"customer_note": "",
	"create_account": false,
	"payment_method": "bacs",
	"payment_data": [
	{
		"key": "wc-bacs-new-payment-method",
		"value": false
	}
	]
}
```

### Errors from Store API

When POSTing a checkout request to the Store API, it may return an error status if there is an issue with the submitted additional fields data.

The error returned will contain the name of the offending field as well as an explanation of the error.

For example, using the example payload from above, if the `namespace/how-did-you-hear-about-us` value is changed to an invalid value (i.e. it is not in the list of options supplied during registration) the response would be:

```json
{
	"code": "rest_invalid_param",
	"message": "Invalid parameter(s): additional_fields",
	"data": {
		"status": 400,
		"params": {
			"additional_fields": "namespace\/how-did-you-hear-about-us is not one of google, facebook, friend, and other."
		},
		"details": {
			"additional_fields": {
				"code": "rest_not_in_enum",
				"message": "namespace\/how-did-you-hear-about-us is not one of google, facebook, friend, and other.",
				"data": {
					"location": "additional",
					"key": "namespace\/how-did-you-hear-about-us"
				}
			}
		}
	}
}
```

The error response contains a `details` section within which is an `additional` fields section. This lists each additional checkout field that encountered an error during processing. Along with each error is a `data` array containing a location which indicates where the field was rendered. This is useful for rendering an error as close to the field as possible.

Errors on fields registered with the `address` location will be reported as part of that address, for example:

```json
{
	"code": "woocommerce_rest_invalid_address",
	"message": "There was a problem with the provided billing address: Government ID is required",
	"data": {
		"errors": {
			"billing": [
				"Government ID is required"
			]
		},
		"status": 400
	}
}
```
Location is not specified here because the address type is specified in the key of the `errors` object.
