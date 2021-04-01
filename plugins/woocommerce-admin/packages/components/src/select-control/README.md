# SelectControl

A search box which filters options while typing,
allowing a user to select from an option from a filtered list.

## Usage

```jsx
const options = [
	{
		key: 'apple',
		label: 'Apple',
		value: { id: 'apple' },
	},
	{
		key: 'apricot',
		label: 'Apricot',
		value: { id: 'apricot' },
	},
];

<SelectControl
	label="Single value"
	onChange={ ( selected ) => setState( { singleSelected: selected } ) }
	options={ options }
	placeholder="Start typing to filter options..."
	selected={ singleSelected }
/>;
```

### Props

| Name                     | Type         | Default    | Description                                                                                                                                                     |
| ------------------------ | ------------ | ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `className`              | string       | `null`     | Class name applied to parent div                                                                                                                                |
| `excludeSelectedOptions` | boolean      | `true`     | Exclude already selected options from the options list                                                                                                          |
| `onFilter`               | function     | `identity` | Add or remove items to the list of options after filtering, passed the array of filtered options and should return an array of options.                         |
| `getSearchExpression`    | function     | `identity` | Function to add regex expression to the filter the results, passed the search query                                                                             |
| `help`                   | string\|node | `null`     | Help text to be appended beneath the input                                                                                                                      |
| `inlineTags`             | boolean      | `false`    | Render tags inside input, otherwise render below input                                                                                                          |
| `label`                  | string       | `null`     | A label to use for the main input                                                                                                                               |
| `onChange`               | function     | `noop`     | Function called when selected results change, passed result list                                                                                                |
| `onSearch`               | function     | `noop`     | Function to run after the search query is updated, passed the search query                                                                                      |
| `options`                | array        | `null`     | (required) An array of objects for the options list. The option along with its key, label and value will be returned in the onChange event                      |
| `placeholder`            | string       | `null`     | A placeholder for the search input                                                                                                                              |
| `selected`               | array        | `[]`       | An array of objects describing selected values. If the label of the selected value is omitted, the Tag of that value will not be rendered inside the search box |
| `maxResults`             | number       | `0`        | A limit for the number of results shown in the options menu. Set to 0 for no limit                                                                              |
| `multiple`               | boolean      | `false`    | Allow multiple option selections                                                                                                                                |
| `showClearButton`        | boolean      | `false`    | Render a 'Clear' button next to the input box to remove its contents                                                                                            |
| `hideBeforeSearch`       | boolean      | `false`    | Only show list options after typing a search query                                                                                                              |
| `staticList`             | boolean      | `false`    | Render results list positioned statically instead of absolutely                                                                                                 |

### onChange value

The onChange value defaults to an array of the selected option(s), but will also reflect what has been passed in the `selected` prop.
If the `selected` prop has the value set as a `string`, the `onChange` method will also be called with a string value - the `key` of the selected option (if multiple is `false`).

Only string or array are the supported types here.
