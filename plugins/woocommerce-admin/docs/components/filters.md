`ReportFilters` (component)
===========================

Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.



Props
-----

### `advancedConfig`

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

`AdvancedFilters` (component)
=============================

Displays a configurable set of filters which can modify query parameters.

Props
-----

### `config`

- **Required**
- Type: Object
  - labels: Object
  - add: String
  - placeholder: String
  - remove: String
  - title: String
  - rules: Array
Object
  - input: Object
- Default: null

The configuration object required to render filters.

### `filterTitle`

- **Required**
- Type: String
- Default: null

Name of this filter, used in translations.

### `path`

- **Required**
- Type: String
- Default: null

The `path` parameter supplied by React-Router.

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form.

`CompareFilter` (component)
===========================

Displays a card + search used to filter results as a comparison between objects.

Props
-----

### `getLabels`

- **Required**
- Type: Function
- Default: null

Function used to fetch object labels via an API request, returns a Promise.

### `labels`

- Type: Object
  - placeholder: String - Label for the search placeholder.
  - title: String - Label for the card title.
  - update: String - Label for button which updates the URL/report.
- Default: `{}`

Object of localized labels.

### `param`

- **Required**
- Type: String
- Default: null

The parameter to use in the querystring.

### `path`

- **Required**
- Type: String
- Default: null

The `path` parameter supplied by React-Router

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form

### `type`

- **Required**
- Type: String
- Default: null

Which type of autocompleter should be used in the Search

`DatePicker` (component)
========================

Select a range of dates or single dates.

Props
-----

### `path`

- **Required**
- Type: String
- Default: null

The `path` parameter supplied by React-Router.

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form.

`FilterPicker` (component)
==========================

Modify a url query parameter via a dropdown selection of configurable options.
This component manipulates the `filter` query parameter.

Props
-----

### `filters`

- **Required**
- Type: Array
  - component: String - A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
  - label: String - The label for this filter. Optional only for custom component filters.
  - path: String - An array representing the "path" to this filter, if nested.
  - subFilters: Array - An array of more filter objects that act as "children" to this item.
This set of filters is shown if the parent filter is clicked.
  - value: String - The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
- Default: null

An array of filters and subFilters to construct the menu.

### `path`

- **Required**
- Type: String
- Default: null

The `path` parameter supplied by React-Router.

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form.

