ReportTable
===

Component that extends `TableCard` to facilitate its usage in reports.

## Usage

```jsx
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`columnPrefsKey` | String | ``null`` | The key for user preferences settings for column visibility
`endpoint` | String | ``null`` | The endpoint to use in API calls to populate the table rows and summary. For example, if `taxes` is provided, data will be fetched from the report `taxes` endpoint (ie: `/wc-analytics/reports/taxes` and `/wc/v4/reports/taxes/stats`). If the provided endpoint doesn't exist, an error will be shown to the user with `ReportError`
`extendItemsMethodNames` | Object
  - getError: String
  - isRequesting: String
  - load: String | ``null`` | Name of the methods available via `select( 'wc-api' )` that will be used to load more data for table items. If omitted, no call will be made and only the data returned by the reports endpoint will be used
`getHeadersContent` | Function | ``null`` | (required) A function that returns the headers object to build the table
`getRowsContent` | Function | ``null`` | (required) A function that returns the rows array to build the table
`getSummary` | Function | ``null`` | A function that returns the summary object to build the table
`itemIdField` | String | ``null`` | The name of the property in the item object which contains the id
`primaryData` | Object | ``{}`` | Primary data of that report. If it's not provided, it will be automatically loaded via the provided `endpoint`
`tableData` | Object | ``{
    items: {
        data: [],
        totalResults: 0,
    },
    query: {},
}`` | Table data of that report. If it's not provided, it will be automatically loaded via the provided `endpoint`
`tableQuery` | Object | ``{}`` | Properties to be added to the query sent to the report table endpoint
`title` | String | ``null`` | (required) String to display as the title of the table
