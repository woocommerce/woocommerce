`TableCard` (component)
=======================

This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
It accepts `headers` for column headers, and `rows` for the table content.
`rowHeader` can be used to define the index of the row header (or false if no header).

`TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
This includes filtering and comparison functionality for report pages.

Props
-----

### `compareBy`

- Type: String
- Default: null

The string to use as a query parameter when comparing row items.

### `compareParam`

- Type: String
- Default: `'filter'`

Url query parameter compare function operates on

### `headers`

- Type: Array
  - hiddenByDefault: Boolean
  - defaultSort: Boolean
  - isSortable: Boolean
  - key: String
  - label: String
  - required: Boolean
- Default: null

An array of column headers (see `Table` props).

### `labels`

- Type: Object
  - compareButton: String
  - downloadButton: String
  - helpText: String
  - placeholder: String
- Default: null

Custom labels for table header actions.

### `ids`

- Type: Array
Number
- Default: null

A list of IDs, matching to the row list so that ids[ 0 ] contains the object ID for the object displayed in row[ 0 ].

### `isLoading`

- Type: Boolean
- Default: `false`

Defines if the table contents are loading.
It will display `TablePlaceholder` component instead of `Table` if that's the case.

### `onQueryChange`

- Type: Function
- Default: `noop`

A function which returns a callback function to update the query string for a given `param`.

### `onColumnsChange`

- Type: Function
- Default: `noop`

A function which returns a callback function which is called upon the user changing the visiblity of columns.

### `onSearch`

- Type: Function
- Default: `noop`

A function which is called upon the user searching in the table header.

### `onSort`

- Type: Function
- Default: `undefined`

A function which is called upon the user changing the sorting of the table.

### `downloadable`

- Type: Boolean
- Default: `false`

Whether the table must be downloadable. If true, the download button will appear.

### `onClickDownload`

- Type: Function
- Default: null

A callback function called when the "download" button is pressed. Optional, if used, the download button will appear.

### `query`

- Type: Object
- Default: `{}`

An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.

### `rowHeader`

- Type: One of type: number, bool
- Default: `0`

An array of arrays of display/value object pairs (see `Table` props).

### `rows`

- Type: Array
Array
  - display: ReactNode
  - value: One of type: string, number, bool
- Default: `[]`

Which column should be the row header, defaults to the first item (`0`) (see `Table` props).

### `rowsPerPage`

- **Required**
- Type: Number
- Default: null

The total number of rows to display per page.

### `searchBy`

- Type: String
- Default: null

The string to use as a query parameter when searching row items.

### `showMenu`

- Type: Boolean
- Default: `true`

Boolean to determine whether or not ellipsis menu is shown.

### `summary`

- Type: Array
  - label: ReactNode
  - value: One of type: string, number
- Default: null

An array of objects with `label` & `value` properties, which display in a line under the table.
Optional, can be left off to show no summary.

### `title`

- **Required**
- Type: String
- Default: null

The title used in the card header, also used as the caption for the content in this table.

### `totalRows`

- **Required**
- Type: Number
- Default: null

The total number of rows (across all pages).

### `baseSearchQuery`

- Type: Object
- Default: `{}`

Pass in query parameters to be included in the path when onSearch creates a new url.

`EmptyTable` (component)
========================

`EmptyTable` displays a blank space with an optional message passed as a children node
with the purpose of replacing a table with no rows.
It mimics the same height a table would have according to the `numberOfRows` prop.



Props
-----

### `numberOfRows`

- Type: Number
- Default: `5`

An integer with the number of rows the box should occupy.

`TablePlaceholder` (component)
==============================

`TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.

Props
-----

### `query`

- Type: Object
- Default: null

An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.

### `caption`

- **Required**
- Type: String
- Default: null

A label for the content in this table.

### `headers`

- Type: Array
  - hiddenByDefault: Boolean
  - defaultSort: Boolean
  - isSortable: Boolean
  - key: String
  - label: ReactNode
  - required: Boolean
- Default: null

An array of column headers (see `Table` props).

### `numberOfRows`

- Type: Number
- Default: `5`

An integer with the number of rows to display.

`TableSummary` (component)
==========================

A component to display summarized table data - the list of data passed in on a single line.



Props
-----

### `data`

- Type: Array
- Default: null

An array of objects with `label` & `value` properties, which display on a single line.

`Table` (component)
===================

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

Props
-----

### `ariaHidden`

- Type: Boolean
- Default: `false`

Controls whether this component is hidden from screen readers. Used by the loading state, before there is data to read.
Don't use this on real tables unless the table data is loaded elsewhere on the page.

### `caption`

- **Required**
- Type: String
- Default: null

A label for the content in this table

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `headers`

- Type: Array
  - defaultSort: Boolean - Boolean, true if this column is the default for sorting. Only one column should have this set.
  - defaultOrder: String - String, asc|desc if this column is the default for sorting. Only one column should have this set.
  - isLeftAligned: Boolean - Boolean, true if this column should be aligned to the left.
  - isNumeric: Boolean - Boolean, true if this column is a number value.
  - isSortable: Boolean - Boolean, true if this column is sortable.
  - key: String - The API parameter name for this column, passed to `orderby` when sorting via API.
  - label: ReactNode - The display label for this column.
  - required: Boolean - Boolean, true if this column should always display in the table (not shown in toggle-able list).
  - screenReaderLabel: String - The label used for screen readers for this column.
- Default: `[]`

An array of column headers, as objects.

### `onSort`

- Type: Function
- Default: `noop`

A function called when sortable table headers are clicked, gets the `header.key` as argument.

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form

### `rows`

- **Required**
- Type: Array
Array
  - display: ReactNode - Display value, used for rendering- strings or elements are best here.
  - value: One of type: string, number, bool
- Default: null

An array of arrays of display/value object pairs.

### `rowHeader`

- Type: One of type: number, bool
- Default: `0`

Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col
is checkboxes, for example). Set to false to disable row headers.

