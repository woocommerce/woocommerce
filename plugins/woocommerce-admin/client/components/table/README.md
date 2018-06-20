Table
=====

This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data). It accepts `headers` for column headers, and `rows` for the table content. `rowHeader` can be used to define the index of the row header (or false if no header). Sortability is enabled by default (and can be turned off), however only string and number columns are sortable.

## How to use:

```jsx
import Table from 'components/table';

render: function() {
  return (
    <Table
      rows={ rows }
      headers={ headers }
      caption={ __( 'Revenue Last Week' ) }
    />
  );
}
```

## Props

* `caption` (required): A label for the content in this table
* `className`: Optional additional classes
* `headers`: An array of column headers
* `rows` (required): An array of arrays of renderable elements, strings or numbers are best for sorting
* `rowHeader`: Which column should be the row header, defaults to the first item (`0`) (but could be set to `1`, if the first col is checkboxes, for example). Set to false to disable row headers.
* `sortable`: Boolean, false if column sorting should be disabled. Defaults to true.

## Rows Format

Row data should be passed to the component as a list of arrays, where each array is a row in the table. Headers are passed in separately as an array of strings. For example, this data would render the following table.

```js
const headers = [ 'Month', 'Orders', 'Revenue' ];
const rows = [
	[ 'January', 10, 530 ],
	[ 'February', 13, 675 ],
	[ 'March', 9, 460 ],
]
```

|   Month  | Orders | Revenue |
| ---------|--------|---------|
| January  |     10 |     530 |
| February |     13 |     675 |
| March    |      9 |     460 |
