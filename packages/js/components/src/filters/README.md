ReportFilters
===

Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`advancedFilters` | Object | `{}` | Config option passed through to `AdvancedFilters`
`siteLocale` | string| `en_US` | The locale of the site. Passed through to `AdvancedFilters`
`currency` | object | {} | The currency of the site. Passed through to `AdvancedFilters`
`filters` | Array | `[]` | Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed
`path` | String | `null` | (required) The `path` parameter supplied by React-Router
`query` | Object | `{}` | The query string represented in object form
`showDatePicker` | Boolean | `true` | Whether the date picker must be shown
`onDateSelect` | Function | `() => {}` | Function to be called after date selection
`onFilterSelect` | Function | `null` | Function to be called after filter selection
`onAdvancedFilterAction` | Function | `null` | Function to be called after an advanced filter action has been taken
`storeDate` | object | `null` | (required) Date utility function object bound to store settings.
