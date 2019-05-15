`EllipsisMenu` (component)
==========================

This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.

Props
-----

### `label`

- **Required**
- Type: String
- Default: null

The label shown when hovering/focusing on the icon button.

### `renderContent`

- Type: Function
- Default: null

A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.

`MenuItem` (component)
======================

`MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`).
`MenuItem`s can also be deemed "clickable", though this is disabled by default because generally the inner component handles
the click event.

Props
-----

### `checked`

- Type: Boolean
- Default: null

Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`.

### `children`

- Type: ReactNode
- Default: null

A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.

### `isCheckbox`

- Type: Boolean
- Default: `false`

Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role).

### `isClickable`

- Type: Boolean
- Default: `false`

Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component
handles the click event.

### `onInvoke`

- **Required**
- Type: Function
- Default: null

A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked
(only if `isClickable` is set).

`MenuTitle` (component)
=======================

`MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
(so this should not be used in place of the `EllipsisMenu` prop `label`).



Props
-----

### `children`

- Type: ReactNode
- Default: null

A renderable component (or string) which will be displayed as the content of this item.

