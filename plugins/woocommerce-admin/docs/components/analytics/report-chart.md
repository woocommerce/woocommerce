`ReportChart` (component)
=========================

Component that renders the chart in reports.

Props
-----

### `filters`

- Type: Array
- Default: null

Filters available for that report.

### `isRequesting`

- Type: Boolean
- Default: `false`

Whether there is an API call running.

### `itemsLabel`

- Type: String
- Default: null

Label describing the legend items.

### `limitProperty`

- Type: String
- Default: null

Allows specifying a property different from the `endpoint` that will be used
to limit the items when there is an active search.

### `mode`

- Type: String
- Default: null

`items-comparison` (default) or `time-comparison`, this is used to generate correct
ARIA properties.

### `path`

- **Required**
- Type: String
- Default: null

Current path

### `primaryData`

- Type: Object
- Default: `{
    data: {
        intervals: [],
    },
    isError: false,
    isRequesting: false,
}`

Primary data to display in the chart.

### `query`

- **Required**
- Type: Object
- Default: null

The query string represented in object form.

### `secondaryData`

- Type: Object
- Default: `{
    data: {
        intervals: [],
    },
    isError: false,
    isRequesting: false,
}`

Secondary data to display in the chart.

### `selectedChart`

- **Required**
- Type: Object
- Default: null

Properties of the selected chart.

