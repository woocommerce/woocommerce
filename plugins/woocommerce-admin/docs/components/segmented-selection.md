`SegmentedSelection` (component)
================================

Create a panel of styled selectable options rendering stylized checkboxes and labels

Props
-----

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `options`

- **Required**
- Type: Array
  - value: String
  - label: String
- Default: null

An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.

### `selected`

- Type: String
- Default: null

Value of selected item.

### `onSelect`

- **Required**
- Type: Function
- Default: null

Callback to be executed after selection

### `name`

- **Required**
- Type: String
- Default: null

This will be the key in the key and value arguments supplied to `onSelect`.

### `legend`

- **Required**
- Type: String
- Default: null

Create a legend visible to screen readers.

