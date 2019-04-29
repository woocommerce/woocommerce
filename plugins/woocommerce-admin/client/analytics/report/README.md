Reports
=======

The core reports offered by WooCommerce live in this folder. The Header is added automatically by the parent Report component, each individual component should contain just the report contents.

## Extending Reports

New reports can be added by third-parties without altering `woocommerce-admin`, by hooking into the reports filter, `woocommerce_admin_reports_list`. For example:

```js
addFilter( 'woocommerce_admin_reports_list', 'analytics/my-report', pages => {
	return [
		...pages,
		{
			report: 'example',
			title: 'My Example Extension',
			component: Report,
		},
	];
} );
```

Each report is defined by an object containing `report`, `title`, `component`.

- `report` (string): The path used to show the report, ex: `/analytics/example`
- `title` (string): The title shown in the breadcrumbs & document title.
- `component` (react component): The component containing the report content- everything on the page under the breadcrumbs header.

The component will get the following props:

- `query` (object): The query string for the current view, can be used to paginate reports, or sort/filter report data.
- `path` (string): The exact path for this view.
- `pathMatch` (string): The route matched for this view, should always be `/analytics/:report`.
- `params` (object): This will contain the `report` from the path, which should match `report` in the page object.

**Note:** Adding your page to `woocommerce_admin_reports_list` does not add the item to the admin menu, you'll need to do that in PHP with the `woocommerce_admin_report_menu_items` filter.
