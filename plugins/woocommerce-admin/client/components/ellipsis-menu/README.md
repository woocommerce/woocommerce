EllipsisMenu
============

This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed. `MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`). `MenuItem`s can also be deemed "clickable", though this is disabled by default because generally the inner component handles the click event.

The last component in this set is `MenuTitle`, which is another valid Menu child but this does not have any accessibility attributes associated (so this should not be used in place of the `EllipsisMenu` prop `label`).

## How to use:

```jsx
import { EllipsisMenu, MenuItem, MenuTitle } from 'components/ellipsis-menu';

render: function() {
  return (
    <EllipsisMenu label="Choose which analytics to display">
      <MenuTitle>Display Stats</MenuTitle>
      <MenuItem onInvoke={ this.toggle }>
        <ToggleControl
          label="Show Customers"
          checked={ this.state.showCustomers }
          onChange={ this.toggle }
        />
      </MenuItem>
      …
    </EllipsisMenu>
  );
}
```

## `EllipsisMenu` Props

* `label` (required): The label shown when hovering/focusing on the icon button.
* `children`: A list of MenuTitle/MenuItem components

## `MenuItem` Props

* `onInvoke` (required): A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked (only if `isClickable` is set).
* `children`: A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
* `isClickable`: Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component handles the click event.

## `MenuTitle` Props

* `children`: A renderable component (or string) which will be displayed as the content of this item.
