`SplitButton` (component)
=========================

A component for displaying a button with a main action plus a secondary set of actions behind a menu toggle.



Props
-----

### `isPrimary`

- Type: Boolean
- Default: `false`

Whether the button is styled as a primary button.

### `mainIcon`

- Type: ReactNode
- Default: null

Icon for the main button.

### `mainLabel`

- Type: String
- Default: null

Label for the main button.

### `onClick`

- Type: Function
- Default: `noop`

Function to activate when the the main button is clicked.

### `menuLabel`

- Type: String
- Default: null

Label to display for the menu of actions, used as a heading on the mobile popover and for accessible text.

### `controls`

- **Required**
- Type: Array
  - icon: One of type: string, element
  - label: String - Label displayed for this button.
  - onClick: Function - Click handler for this button.
- Default: null

An array of additional actions. Accepts additional icon, label, and onClick props.

### `className`

- Type: String
- Default: null

Additional CSS classes.

