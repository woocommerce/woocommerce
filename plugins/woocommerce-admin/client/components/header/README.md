Header
====

A basic component for the app header. The header outputs breadcrumbs via the `sections` prop (required) and a toggle button to show the timeline sidebar (hidden via CSS if no applicable to the page).

## How to use:

```jsx
import Header from 'components/header';

render: function() {
	return (
		<Header sections={ [
			<a href={ getAdminLink( 'admin.php?page=wooanalytics' ) }>{ __( 'Analytics', 'woo-dash' ) }</a>,
			__( 'Report Title', 'woo-dash' ),
		] } />
  	);
}
```

## Props

* `sections` (required): Used to generate breadcrumbs. Accepts a single node/elemnt or an array of nodes.
* `onToggle` (required): The toggle callback when "open sidebar" button is clicked.
* `isSidebarOpen`: Boolean describing whether the sidebar is toggled visible.

Note: `onToggle` & `isSidebarOpen` are passed through the `Slot` call, and aren't required when using `<Header />` in section components.
