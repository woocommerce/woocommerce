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

