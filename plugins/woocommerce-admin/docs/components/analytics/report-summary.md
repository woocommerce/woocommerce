ReportSummary
===

Component to render summary numbers in reports.

## Usage

```jsx
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`charts` | Array | ``null`` | (required) Properties of all the charts available for that report
`endpoint` | String | ``null`` | (required) The endpoint to use in API calls to populate the Summary Numbers. For example, if `taxes` is provided, data will be fetched from the report `taxes` endpoint (ie: `/wc-analytics/reports/taxes/stats`). If the provided endpoint doesn't exist, an error will be shown to the user with `ReportError`
`limitProperties` | Array | ``null`` | Allows specifying properties different from the `endpoint` that will be used to limit the items when there is an active search
`query` | Object | ``null`` | (required) The query string represented in object form
`isRequesting` | Boolean | ``null`` | Whether there is an API call running
`selectedChart` | Object
  - key: String - Key of the selected chart.
  - label: String - Chart label.
  - order: One of: 'asc', 'desc'
  - orderby: String - Order by query argument.
  - type: One of: 'average', 'number', 'currency' | ``null`` | (required) Properties of the selected chart
`summaryData` | Object | ``{
    totals: {
        primary: {},
        secondary: {},
    },
    isError: false,
    isRequesting: false,
}`` | Data to display in the SummaryNumbers
`report` | String | ``null`` | Report name, if different than the endpoint
