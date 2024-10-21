# Reports

The core reports offered by WooCommerce live in this folder. The Header is added automatically by the parent Report component, each individual component should contain just the report contents.

## Extending Reports

New reports can be added by third-parties without altering `woocommerce-admin`. First, a page will need to be registered in PHP to populate menu items.

```php
function register_page( $report_pages ) {
	$report_pages[] = array(
		'id'     => 'extension-example',
		'title'  => __( 'Example', 'plugin-domain' ),
		'parent' => 'woocommerce-analytics',
		'path'   => '/analytics/example',
	);
	return $report_pages;
}

add_filter( 'woocommerce_analytics_report_menu_items', 'register_page' );
```

Each menu item is defined by an array containing `id`, `title`, `parent`, and `path`.

-   `report` (string): The report's id.
-   `title` (string): The title shown in the sidebar.
-   `parent` (string): The item's parent in the navigational hierarchy.
-   `path` (string): The report's relative path.

Next, hook into the JavaScript reports filter, `woocommerce_admin_reports_list`, to add a report component.

```js
addFilter(
	'woocommerce_admin_reports_list',
	'analytics/my-report',
	( pages ) => {
		return [
			...pages,
			{
				report: 'example',
				title: 'My Example Extension',
				component: Report,
				navArgs: {
					id: 'my-example-report',
				},
			},
		];
	}
);
```

Each report is defined by an object containing `report`, `title`, `component`, and optionally `navArgs`.

-   `report` (string): The path used to show the report, ex: `/analytics/example`
-   `title` (string): The title shown in the breadcrumbs & document title.
-   `component` (react component): The component containing the report content- everything on the page under the breadcrumbs header.
-   `navArgs` (object): Arguments used for the new navigation experience, typically used to supply a matching ID for server-side registered menu items.

The component will get the following props:

-   `query` (object): The query string for the current view, can be used to paginate reports, or sort/filter report data.
-   `path` (string): The exact path for this view.
-   `pathMatch` (string): The route matched for this view, should always be `/analytics/:report`.
-   `params` (object): This will contain the `report` from the path, which should match `report` in the page object.
