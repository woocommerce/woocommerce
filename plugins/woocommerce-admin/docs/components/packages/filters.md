`ReportFilters` (component)
===========================

Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.



Props
-----

### `advancedFilters`

- Type: Object
- Default: `{}`

Config option passed through to `AdvancedFilters`

### `filters`

- Type: Array
- Default: `[]`

Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.

### `path`

- **Required**
- Type: String
- Default: null

The `path` parameter supplied by React-Router

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form

### `showDatePicker`

- Type: Boolean
- Default: `true`

Whether the date picker must be shown.

### `onDateSelect`

- Type: Function
- Default: `() => {}`

Function to be called after date selection.

### `onFilterSelect`

- Type: Function
- Default: null

Function to be called after filter selection.

### `onAdvancedFilterAction`

- Type: Function
- Default: null

Function to be called after an advanced filter action has been taken.

