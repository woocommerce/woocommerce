# CSV Export

A set of functions to convert data into CSV values, and enable a browser download of the CSV data.

## Installation

Install the module

```bash
pnpm install @woocommerce/csv-export --save
```

## Usage

```js
onClick = () => {
	// Create a file name based on a title and optional query. Will return a timestamped
	// name, for example: revenue-2018-11-01-interval-month.csv
	const name = generateCSVFileName( 'revenue', { interval: 'month' } );

	// Create a string of CSV data, `headers` is an array of row headers, put at the top
	// of the file. `rows` is a 2 dimensional array. Each array is a line in the file,
	// separated by newlines. The second-level arrays are the data points in each row.
	const data = generateCSVDataFromTable( headers, rows );

	// Triggers a browser UI to save a file, named the first argument, with the contents of
	// the second argument.
	downloadCSVFile( name, data );
}
```

### generateCSVDataFromTable(headers, rows) ⇒ `String`

Generates a CSV string from table contents

**Returns**: `String` - Table contents in a CSV format

| Param | Type | Description |
| --- | --- | --- |
| headers | `Array.&lt;Object&gt;` | Object with table header information |
| rows | `Array.Array.&lt;Object&gt;` | Object with table rows information |

### generateCSVFileName([name], [params]) ⇒ `String`

Generates a file name for CSV files based on the provided name, the current date
and the provided params, which are all appended with hyphens.

**Returns**: `String` - Formatted file name

| Param | Type | Default | Description |
| --- | --- | --- | --- |
| [name] | `String` | `&#x27;&#x27;` | Name of the file |
| [params] | `Object` | `{}` | Object of key-values to append to the file name |

### downloadCSVFile(fileName, content)

Downloads a CSV file with the given file name and contents

| Param | Type | Description |
| --- | --- | --- |
| fileName | `String` | Name of the file to download |
| content | `String` | Contents of the file to download |
