Layout
======

This component handles the layout of the WooCommerce app. This also controls the routing, and which component should be shown on each page.

## Layout

The `Layout` component sets up the structure of the page, using the components described below. This also handles the sidebar state (stored in component state), and passes that through to Sidebar & Header, so the toggle buttons can work.

## Header

The Header component used in each section automatically fills into the "header" slot defined here. We're using [react-slot-fill](https://github.com/camwest/react-slot-fill) to avoid a duplicated `div` wrapper from Gutenberg's implementation. See the [header component docs](../components/header) for more information.

## Notices

This component will house the list of high priority notices. This appears on every page. _Currently just a placeholder div._

## Sidebar

This component contains the sidebar content. This is shown on every page, but conditionally hidden behind a toggle button in the Header.

## Controller

`layout/controller.js` has two exports, a `<Controller />` component and a `getPages` function.

### `getPages`

This function returns an array of objects, each describing a page in the app. The properties in each object are:

- `container`: A component, rendered in the main content area of the Layout
- `path`: The path this component should show up on (this should be unique to each entry)
- `wpMenu`: The ID of the menu item in the sidebar, used to toggle on/off the current menu item classes
- `hasOpenSidebar`: A boolean describing whether this page should show the sidebar open on larger screens

### `<Controller />`

This component pulls out the current page from `getPages`, and renders the container component defined in the object.

## `Section` and `H`

These components are pulled from `layout/section`, and are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate heading level.

For example…

```jsx
<H>My important title</H>
<Section>
	<H>This subtitle</H>
	<p>Some content</p>
	<H>Another subtitle</H>
	<p>More content</p>
</Section>
```

will render as

```html
<h2>My important title</h2>
<div>
	<h3>This subtitle</h3>
	<p>Some content</p>
	<h3>Another subtitle</h3>
	<p>More content</p>
</div>
```

Note that H starts at level 2, since there should be only 1 `h1` on each page and we set that separately in `<Header />`.

### Props for `<H />`

Any props passed to `H` will pass down to the `h*` element, so be sure to use only valid HTML properties.

### Props for `<Section />`

- `component`: The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used.
- `children`: The children inside this section, rendered in the `component`. This increases the context level for the next heading used.
- `...props`: All other props are passed to the wrapper component, or ignored if `component === false`.
