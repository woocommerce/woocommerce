`ReportChart` (component)
=========================

Component that renders the chart in reports.

Props
-----

### `filters`

- Type: Array
- Default: null

Filters available for that report.

### `itemsLabel`

- Type: String
- Default: null

Label describing the legend items.

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

- **Required**
- Type: Object
- Default: null

Primary data to display in the chart.

### `query`

- **Required**
- Type: Object
- Default: null

The query string represented in object form.

### `secondaryData`

- **Required**
- Type: Object
- Default: null

Secondary data to display in the chart.

### `selectedChart`

- **Required**
- Type: Object
- Default: null

Properties of the selected chart.

