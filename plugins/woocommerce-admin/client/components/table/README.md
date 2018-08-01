`Table` Components
==================

This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data). It accepts `headers` for column headers, and `rows` for the table content. `rowHeader` can be used to define the index of the row header (or false if no header).

`TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`. This includes filtering and comparison functionality for report pages.

`Table` itself can be used outside of the Card + filtering context for displaying any tabular data.

`TableSummary` can also be used alone, and will display the list of data passed in on a single line.

## How to use:

```jsx
import { TableCard } from 'components/table';

render: function() {
  return (
    <TableCard
      title="Revenue Last Week"
      rows={ rows }
      headers={ headers }
      onQueryChange={ this.onQueryChange }
      query={ query }
      summary={ summary }
    />
  );
}
```

## Props

### `TableCard` props

* `headers`: An array of column headers
* `onQueryChange`: A function which returns a callback function to update the query string for a given `param`.
* `onClickDownload`: A callback function which handles then "download" button press. Optional, if not used, the button won't appear.
* `query`: An object of the query parameters passed to the page, ex `{ page: 2, per_page: 5 }`.
* `rows` (required): An array of arrays of display/value object pairs. `display` is used for rendering, strings or elements are best here. `value` is used for sorting, and should be a string or number. A column with `false` value will not be sortable.
* `rowHeader`: Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col is checkboxes, for example). Set to false to disable row headers.
* `summary`: An array of objects with `label` & `value` properties, which display in a line under the table. Optional, can be left off to show no summary.
* `title` (required): The title used in the card header, also used as the caption for the content in this table


`rows`, `headers`, `rowHeader`, and `title` are passed through to `<Table />`. `summary` is passed through as `data` to `<TableSummary />`. `query.page`, `query.per_page`, and `onQueryChange` are passed through to `<Pagination />`.

### `Table` props

* `caption` (required): A label for the content in this table
* `className`: Optional additional classes
* `headers`: An array of column headers
* `rows` (required): An array of arrays of renderable elements, strings or numbers are best for sorting
* `rowHeader`: Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col is checkboxes, for example). Set to false to disable row headers.

### `TableSummary` props

* `data`: An array of objects with `label` & `value` properties, which display on a single line.

## Rows Format

Row data should be passed to the component as a list of arrays, where each array is a row in the table. Headers are passed in separately as an array of strings. For example, this data would render the following table.

Each row-cell should be an object with a `display` and `value` property, to enable consistent sortability.

```js
const headers = [ 'Month', 'Orders', 'Revenue' ];
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
