`ReportSummary` (component)
===========================

Component to render summary numbers in reports.

Props
-----

### `charts`

- **Required**
- Type: Array
- Default: null

Properties of all the charts available for that report.

### `endpoint`

- **Required**
- Type: String
- Default: null

The endpoint to use in API calls to populate the Summary Numbers.
For example, if `taxes` is provided, data will be fetched from the report
`taxes` endpoint (ie: `/wc/v4/reports/taxes/stats`). If the provided endpoint
doesn't exist, an error will be shown to the user with `ReportError`.

### `query`

- **Required**
- Type: Object
- Default: null

The query string represented in object form.

### `selectedChart`

- **Required**
- Type: Object
  - key: String - Key of the selected chart.
- Default: null

Properties of the selected chart.

