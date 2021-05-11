# SettingsForm

A component to handle form state and provide input helper props.

## Usage

```jsx
const initialValues = { firstName: '' };

<SettingsForm
	fields={ fields }
	onSubmit={ ( values ) => {
		setSubmitted( values );
	} }
	isBusy={ false }
	onButtonClick={ () => {} }
	onChange={ () => {} }
	validate={ () => ( {} ) }
	buttonLabel="Submit"
/>;
```

### Props

| Name            | Type     | Default   | Description                                                                                                                                                             |
| --------------- | -------- | --------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `fields`        | {} or [] | []        | An object to describe the structure and types of all fields, matching the structure returned by the [Settings API](https://docs.woocommerce.com/document/settings-api/) |
| `isBusy`        | Boolean  | false     | Boolean indicating busy state of submit button                                                                                                                          |
| `onButtonClick` | Function | `noop`    | Callback function executed when submit button is clicked (in addition to form submission)                                                                               |
| `onSubmit`      | Function | `noop`    | Function to call when a form is submitted with valid fields                                                                                                             |
| `onChange`      | Function | `noop`    | Function to call when any values on the form are changed                                                                                                                |
| `validate`      | Function | `noop`    | A function that is passed a list of all values and should return an `errors` object with error response                                                                 |
| `buttonLabel`   | String   | "Proceed" | Label for submit button.                                                                                                                                                |

### Fields structure

Please reference the [WordPress settings API documentation](https://docs.woocommerce.com/document/settings-api/) to better understand the structure expected for the fields property. This component accepts the object returned via the `settings` property when querying a gateway via the API, or simply the array provided by `Object.values(settings)`.

### Currently Supported Types

-   Text
-   Password
-   Checkbox
-   Select
