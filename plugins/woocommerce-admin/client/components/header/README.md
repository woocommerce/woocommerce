Header
====

A basic component for the app header. The header outputs breadcrumbs via the `sections` prop (required) and access to the timeline via the `showTimeline` prop (optional).

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
* `showTimeline` (default: true): The header provides access to the WooCommerce timeline from any page. Passing `false` to this prop will hide the timeline icon.