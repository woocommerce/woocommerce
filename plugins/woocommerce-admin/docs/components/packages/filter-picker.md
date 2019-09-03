`FilterPicker` (component)
==========================

Modify a url query parameter via a dropdown selection of configurable options.
This component manipulates the `filter` query parameter.

Props
-----

### `config`

- **Required**
- Type: Object
  - label: String - A label above the filter selector.
  - staticParams: Array - Url parameters to persist when selecting a new filter.
  - param: String - The url paramter this filter will modify.
  - defaultValue: String - The default paramter value to use instead of 'all'.
  - showFilters: Function - Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
  - filters: Array
  - chartMode: One of: 'item-comparison', 'time-comparison'
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

### `onFilterSelect`

- Type: Function
- Default: `() => {}`

Function to be called after filter selection.

