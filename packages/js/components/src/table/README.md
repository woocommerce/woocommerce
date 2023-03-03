TableCard
===

This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
It accepts `headers` for column headers, and `rows` for the table content.
`rowHeader` can be used to define the index of the row header (or false if no header).

`TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
This includes filtering and comparison functionality for report pages.

## Usage

```jsx
const headers = [
	{ key: 'month', label: 'Month' },
	{ key: 'orders', label: 'Orders' },
	{ key: 'revenue', label: 'Revenue' },
];
const rows = [
	[
		{ display: 'January', value: 1 },
		{ display: 10, value: 10 },
		{ display: '$530.00', value: 530 },
	],
	[
		{ display: 'February', value: 2 },
		{ display: 13, value: 13 },
		{ display: '$675.00', value: 675 },
	],
	[
		{ display: 'March', value: 3 },
		{ display: 9, value: 9 },
		{ display: '$460.00', value: 460 },
	],
];
const summary = [
	{ label: 'Gross Income', value: '$830.00' },
	{ label: 'Taxes', value: '$96.32' },
	{ label: 'Shipping', value: '$50.00' },
];

<TableCard
	title="Revenue last week"
	rows={ rows }
	headers={ headers }
	query={ { page: 2 } }
	rowsPerPage={ 7 }
	totalRows={ 10 }
	summary={ summary }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`compareBy` | String | `null` | The string to use as a query parameter when comparing row items
`compareParam` | String | `'filter'` | Url query parameter compare function operates on
`headers` | Array | `null` | An array of column headers (see `Table` props)
`labels` | Object | `null` | Custom labels for table header actions
`ids` | Array | `null` | A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ]
`isLoading` | Boolean | `false` | Defines if the table contents are loading. It will display `TablePlaceholder` component instead of `Table` if that's the case
`onQueryChange` | Function | `noop` | A function which returns a callback function to update the query string for a given `param`
`onColumnsChange` | Function | `noop` | A function which returns a callback function which is called upon the user changing the visiblity of columns
`onSearch` | Function | `noop` | A function which is called upon the user searching in the table header
`onSort` | Function | `undefined` | A function which is called upon the user changing the sorting of the table
`downloadable` | Boolean | `false` | Whether the table must be downloadable. If true, the download button will appear
`onClickDownload` | Function | `null` | A callback function called when the "download" button is pressed. Optional, if used, the download button will appear
`query` | Object | `{}` | An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`
`rowHeader` | One of type: number, bool | `0` | Which column should be the row header, defaults to the first item (`0`) (see `Table` props)
`rows` | Array | `[]` | (required) An array of arrays of display/value object pairs (see `Table` props)
`rowsPerPage` | Number | `null` | (required) The total number of rows to display per page
`searchBy` | String | `null` | The string to use as a query parameter when searching row items
`showMenu` | Boolean | `true` | Boolean to determine whether or not ellipsis menu is shown
`summary` | Array | `null` | An array of objects with `label` & `value` properties, which display in a line under the table. Optional, can be left off to show no summary
`title` | String | `null` | (required) The title used in the card header, also used as the caption for the content in this table
`totalRows` | Number | `null` | (required) The total number of rows (across all pages)
`baseSearchQuery` | Object | `{}` | Pass in query parameters to be included in the path when onSearch creates a new url
`rowKey` | Function(row, index): string | `null` | Function used for the row key.

### `labels` structure

Table header action labels object with properties:

- `compareButton`: String - Compare button label
- `downloadButton`: String - Download button label
- `helpText`: String - 
- `placeholder`: String - 

### `summary` structure

Array of summary items objects with properties:

- `label`: ReactNode
- `value`: One of type: string, number 


EmptyTable
===

`EmptyTable` displays a blank space with an optional message passed as a children node
with the purpose of replacing a table with no rows.
It mimics the same height a table would have according to the `numberOfRows` prop.

## Usage

```jsx
<EmptyTable>
	There are no entries.
</EmptyTable>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`numberOfRows` | Number | `5` | An integer with the number of rows the box should occupy


TablePlaceholder
===

`TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.

## Usage

```jsx
const headers = [
	{ key: 'month', label: 'Month' },
	{ key: 'orders', label: 'Orders' },
	{ key: 'revenue', label: 'Revenue' },
];

<TablePlaceholder
	caption="Revenue last week"
	headers={ headers }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`query` | Object | `null` | An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`
`caption` | String | `null` | (required) A label for the content in this table
`headers` | Array | `null` | An array of column headers (see `Table` props)
`numberOfRows` | Number | `5` | An integer with the number of rows to display


TableSummary
===

A component to display summarized table data - the list of data passed in on a single line.

## Usage

```jsx
const summary = [
	{ label: 'Gross Income', value: '$830.00' },
	{ label: 'Taxes', value: '$96.32' },
	{ label: 'Shipping', value: '$50.00' },
];

<TableSummary data={ summary } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`data` | Array | `null` | An array of objects with `label` & `value` properties, which display on a single line

TableSummaryPlaceholder
===

A component to display a placeholder box for `TableSummary`. There is no prop for this component.

## Usage

```jsx
<TableSummaryPlaceholder />
```

Table
===

A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.

Row data should be passed to the component as a list of arrays, where each array is a row in the table.
Headers are passed in separately as an array of objects with column-related properties. For example,
this data would render the following table.

```js
const headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];
const rows = [
	[
		{ display: 'January', value: 1 },
		{ display: 10, value: 10 },
		{ display: '$530.00', value: 530 },
	],
	[
		{ display: 'February', value: 2 },
		{ display: 13, value: 13 },
		{ display: '$675.00', value: 675 },
	],
	[
		{ display: 'March', value: 3 },
		{ display: 9, value: 9 },
		{ display: '$460.00', value: 460 },
	],
]
```

|   Month  | Orders | Revenue |
| ---------|--------|---------|
| January  |     10 | $530.00 |
| February |     13 | $675.00 |
| March    |      9 | $460.00 |

## Usage

```jsx
<Table
	caption="Revenue last week"
	rows={ rows }
	headers={ headers }
	rowKey={ row => row.display }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`ariaHidden` | Boolean | `false` | Controls whether this component is hidden from screen readers. Used by the loading state, before there is data to read. Don't use this on real tables unless the table data is loaded elsewhere on the page
`caption` | String | `null` | (required) A label for the content in this table
`className` | String | `null` | Additional CSS classes
`headers` | Array | `[]` | An array of column headers, as objects
`onSort` | Function | `noop` | A function called when sortable table headers are clicked, gets the `header.key` as argument
`query` | Object | `{}` | The query string represented in object form
`rows` | Array | `null` | (required) An array of arrays of display/value object pairs
`rowHeader` | One of type: number, bool | `0` | Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col is checkboxes, for example). Set to false to disable row headers
`rowKey` | Function(row, index): string | `null` | Function used to get the row key.
`emptyMessage` | String | `undefined` | Customize the message to show when there are no rows in the table.

### `headers` structure

Array of column header objects with properties:

- `defaultSort`: Boolean - Boolean, true if this column is the default for sorting. Only one column should have this set.
- `defaultOrder`: String - String, asc|desc if this column is the default for sorting. Only one column should have this set.
- `isLeftAligned`: Boolean - Boolean, true if this column should be aligned to the left. If not set, it will fallback to this value `! isNumeric`, i.e. text fields are left-aligned and numeric fields are right-aligned.
- `isNumeric`: Boolean - Boolean, true if this column is a number value.
- `isSortable`: Boolean - Boolean, true if this column is sortable.
- `key`: String - The API parameter name for this column, passed to `orderby` when sorting via API.
- `label`: ReactNode - The display label for this column.
- `required`: Boolean - Boolean, true if this column should always display in the table (not shown in toggle-able list).
- `screenReaderLabel`: String - The label used for screen readers for this column. 

### `rows` structure

Array of arrays representing rows and columns. Column object properties:

- `display`: ReactNode - Display value, used for rendering - strings or elements are best here.
- `value`: One of type: string, number, bool
