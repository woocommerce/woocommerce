Header
====

A basic component for the app header. The header outputs breadcrumbs via the `sections` prop (required) and a toggle button to show the timeline sidebar (hidden via CSS if no applicable to the page). It also sets the document title.

## How to use:

```jsx
import Header from 'components/header';

render: function() {
	return (
		<Header
			sections={ [
				[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
				__( 'Report Title', 'woo-dash' ),
			] }
		/>
  	);
}
```

## Props

* `sections` (required): Used to generate breadcrumbs. Accepts a single items or an array of items. To make an item a link, wrap it in an array with a relative link (example: `[ '/analytics', __( 'Analytics', 'woo-dash' ) ]` ).
* `onToggle` (required): The toggle callback when "open sidebar" button is clicked.
* `isSidebarOpen`: Boolean describing whether the sidebar is toggled visible.
* `isEmbedded`: Boolean describing if the header is embedded on an existing wp-admin page. False if rendered as part of a full react page.

Note: `onToggle` & `isSidebarOpen` are passed through the `Slot` call, and aren't required when using `<Header />` in section components.
