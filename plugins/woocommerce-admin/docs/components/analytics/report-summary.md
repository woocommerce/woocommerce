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

### `limitProperties`

- Type: Array
- Default: null

Allows specifying properties different from the `endpoint` that will be used
to limit the items when there is an active search.

### `query`

- **Required**
- Type: Object
- Default: null

The query string represented in object form.

### `isRequesting`

- Type: Boolean
- Default: null

Whether there is an API call running.

### `selectedChart`

- **Required**
- Type: Object
  - key: String - Key of the selected chart.
  - label: String - Chart label.
  - order: One of: 'asc', 'desc'
  - orderby: String - Order by query argument.
  - type: One of: 'average', 'number', 'currency'
- Default: null

Properties of the selected chart.

### `summaryData`

- Type: Object
- Default: `{
    totals: {
        primary: {},
        secondary: {},
    },
    isError: false,
    isRequesting: false,
}`

Data to display in the SummaryNumbers.

