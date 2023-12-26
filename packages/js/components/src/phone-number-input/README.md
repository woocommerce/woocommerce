# PhoneNumberInput

An international phone number input with a country code select and a phone textfield which supports numbers, spaces and hyphens. And returns the full number as it is, in E.164 format, and the selected country alpha2.

Includes mobile phone numbers validation.

## Usage

```jsx
<PhoneNumberInput
	value={ phoneNumber }
	onChange={ ( value, e164, country ) => setState( value ) }
/>
```

### Props

| Name             | Type     | Default                 | Description                                                                                             |
| ---------------- | -------- | ----------------------- | ------------------------------------------------------------------------------------------------------- |
| `value`          | String   | `undefined`             | (Required) Phone number with spaces and hyphens                                                         |
| `onChange`       | Function | `undefined`             | (Required) Callback function when the value changes                                                     |
| `id`             | String   | `undefined`             | ID for the input element, to bind a `<label>`                                                           |
| `className`      | String   | `undefined`             | Additional class name applied to parent `<div>`                                                         |
| `selectedRender` | Function | `defaultSelectedRender` | Render function for the selected country, displays the country flag and code by default.                |
| `itemRender`     | Function | `itemRender`            | Render function for each country in the dropdown, displays the country flag, name, and code by default. |
| `arrowRender`    | Function | `defaultArrowRender`    | Render function for the dropdown arrow, displays a chevron down icon by default.                        |

### `onChange` params

-   `value`: Phone number with spaces and hyphens. e.g. `+1 234-567-8901`
-   `e164`: Phone number in E.164 format. e.g. `+12345678901`
-   `country`: Country alpha2 code. e.g. `US`
