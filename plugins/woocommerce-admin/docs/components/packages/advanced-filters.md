`AdvancedFilters` (component)
=============================

Displays a configurable set of filters which can modify query parameters.

Props
-----

### `config`

- **Required**
- Type: Object
  - title: String
  - filters: Object
  - labels: Object
  - add: String
  - remove: String
  - rule: String
  - title: String
  - filter: String
  - rules: Array
Object
  - input: Object
- Default: null

The configuration object required to render filters.

### `path`

- **Required**
- Type: String
- Default: null

Name of this filter, used in translations.

### `query`

- Type: Object
- Default: `{}`

The query string represented in object form.

### `onAdvancedFilterAction`

- Type: Function
- Default: `() => {}`

Function to be called after an advanced filter action has been taken.

