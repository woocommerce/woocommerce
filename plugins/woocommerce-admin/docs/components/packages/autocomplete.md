`Autocomplete` (component)
==========================

A search box which filters options while typing,
allowing a user to select from an option from a filtered list.

Props
-----

### `className`

- Type: String
- Default: null

Class name applied to parent div.

### `excludeSelectedOptions`

- Type: Boolean
- Default: `true`

Exclude already selected options from the options list.

### `onFilter`

- Type: Function
- Default: `identity`

Add or remove items to the list of options after filtering,
passed the array of filtered options and should return an array of options.

### `getSearchExpression`

- Type: Function
- Default: `identity`

Function to add regex expression to the filter the results, passed the search query.

### `help`

- Type: One of type: string, node
- Default: null

Help text to be appended beneath the input.

### `inlineTags`

- Type: Boolean
- Default: `false`

Render tags inside input, otherwise render below input.

### `label`

- Type: String
- Default: null

A label to use for the main input.

### `onChange`

- Type: Function
- Default: `noop`

Function called when selected results change, passed result list.

### `onSearch`

- Type: Function
- Default: `noop`

Function to run after the search query is updated, passed the search query.

### `options`

- **Required**
- Type: Array
  - isDisabled: Boolean
  - key: One of type: number, string
  - keywords: Array
String
  - label: String
  - value: *
- Default: null

An array of objects for the options list.  The option along with its key, label and
value will be returned in the onChange event.

### `placeholder`

- Type: String
- Default: null

A placeholder for the search input.

### `selected`

- Type: Array
  - key: One of type: number, string
  - label: String
- Default: `[]`

An array of objects describing selected values. If the label of the selected
value is omitted, the Tag of that value will not be rendered inside the
search box.

### `maxResults`

- Type: Number
- Default: `0`

A limit for the number of results shown in the options menu.  Set to 0 for no limit.

### `multiple`

- Type: Boolean
- Default: `false`

Allow multiple option selections.

### `showClearButton`

- Type: Boolean
- Default: `false`

Render a 'Clear' button next to the input box to remove its contents.

### `hideBeforeSearch`

- Type: Boolean
- Default: `false`

Only show list options after typing a search query.

### `staticList`

- Type: Boolean
- Default: `false`

Render results list positioned statically instead of absolutely.

