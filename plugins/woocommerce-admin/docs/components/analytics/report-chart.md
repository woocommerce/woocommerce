ReportChart
===

Component that renders the chart in reports.

## Usage

```jsx
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`filters` | Array | ``null`` | Filters available for that report
`isRequesting` | Boolean | ``false`` | Whether there is an API call running
`itemsLabel` | String | ``null`` | Label describing the legend items
`limitProperties` | Array | ``null`` | Allows specifying properties different from the `endpoint` that will be used to limit the items when there is an active search
`mode` | String | ``null`` | `items-comparison` (default) or `time-comparison`, this is used to generate correct ARIA properties
`path` | String | ``null`` | (required) Current path
`primaryData` | Object | ``{
    data: {
        intervals: [],
    },
    isError: false,
    isRequesting: false,
}`` | Primary data to display in the chart
`query` | Object | ``null`` | (required) The query string represented in object form
`secondaryData` | Object | ``{
    data: {
        intervals: [],
    },
    isError: false,
    isRequesting: false,
}`` | Secondary data to display in the chart
`selectedChart` | Object
  - key: String - Key of the selected chart.
  - label: String - Chart label.
  - order: One of: 'asc', 'desc'
  - orderby: String - Order by query argument.
  - type: One of: 'average', 'number', 'currency' | ``null`` | (required) Properties of the selected chart
