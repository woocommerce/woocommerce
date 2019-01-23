`ReportTable` (component)
=========================

Component that extends `TableCard` to facilitate its usage in reports.

Props
-----

### `columnPrefsKey`

- Type: String
- Default: null

The key for user preferences settings for column visibility.

### `endpoint`

- Type: String
- Default: null

The endpoint to use in API calls to populate the table rows and summary.
For example, if `taxes` is provided, data will be fetched from the report
`taxes` endpoint (ie: `/wc/v4/reports/taxes` and `/wc/v4/reports/taxes/stats`).
If the provided endpoint doesn't exist, an error will be shown to the user
with `ReportError`.

### `extendItemsMethodNames`

- Type: Object
  - getError: String
  - isRequesting: String
  - load: String
- Default: null

Name of the methods available via `select( 'wc-api' )` that will be used to
load more data for table items. If omitted, no call will be made and only
the data returned by the reports endpoint will be used.

### `getHeadersContent`

- **Required**
- Type: Function
- Default: null

A function that returns the headers object to build the table.

### `getRowsContent`

- **Required**
- Type: Function
- Default: null

A function that returns the rows array to build the table.

### `getSummary`

- Type: Function
- Default: null

A function that returns the summary object to build the table.

### `itemIdField`

- Type: String
- Default: null

The name of the property in the item object which contains the id.

### `primaryData`

- **Required**
- Type: Object
- Default: null

Primary data of that report. If it's not provided, it will be automatically
loaded via the provided `endpoint`.

### `tableData`

- Type: Object
- Default: `{}`

Table data of that report. If it's not provided, it will be automatically
loaded via the provided `endpoint`.

### `tableQuery`

- Type: Object
- Default: `{}`

Properties to be added to the query sent to the report table endpoint.

### `title`

- **Required**
- Type: String
- Default: null

String to display as the title of the table.

