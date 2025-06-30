Layout
======

This component handles the layout of the WooCommerce app. This also controls the routing, and which component should be shown on each page.

## Layout

The `Layout` component sets up the structure of the page, using the components described below.

## Notices

This component will house the list of high priority notices. This appears on every page. _Currently just a placeholder div._

## Controller

`layout/controller.js` has two exports, a `<Controller />` component and a `getPages` function.

### `getPages`

This function returns an array of objects, each describing a page in the app. The properties in each object are:

- `container`: A component, rendered in the main content area of the Layout
- `path`: The path this component should show up on (this should be unique to each entry)
- `wpMenu`: The ID of the menu item in the  wp-admin sidebar, used to toggle on/off the current menu item classes

### `<Controller />`

This component pulls out the current page from `getPages`, and renders the container component defined in the object.
