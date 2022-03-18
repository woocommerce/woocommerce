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
	// For header format, see https://woocommerce.github.io/woocommerce-admin/#/components/table?id=headers-2
	// For rows format, see https://woocommerce.github.io/woocommerce-admin/#/components/table?id=rows-1
	const data = generateCSVDataFromTable( headers, rows );

	// Triggers a browser UI to save a file, named the first argument, with the contents of
	// the second argument.
	downloadCSVFile( name, data );
}
```

### generateCSVDataFromTable(headers, rows) ⇒ <code>String</code>
Generates a CSV string from table contents

**Returns**: <code>String</code> - Table contents in a CSV format

| Param | Type | Description |
| --- | --- | --- |
| headers | <code>Array.&lt;Object&gt;</code> | Object with table header information |
| rows | <code>Array.Array.&lt;Object&gt;</code> | Object with table rows information |

### generateCSVFileName([name], [params]) ⇒ <code>String</code>
Generates a file name for CSV files based on the provided name, the current date
and the provided params, which are all appended with hyphens.

**Returns**: <code>String</code> - Formatted file name

| Param | Type | Default | Description |
| --- | --- | --- | --- |
| [name] | <code>String</code> | <code>&#x27;&#x27;</code> | Name of the file |
| [params] | <code>Object</code> | <code>{}</code> | Object of key-values to append to the file name |

### downloadCSVFile(fileName, content)
Downloads a CSV file with the given file name and contents

| Param | Type | Description |
| --- | --- | --- |
| fileName | <code>String</code> | Name of the file to download |
| content | <code>String</code> | Contents of the file to download |
