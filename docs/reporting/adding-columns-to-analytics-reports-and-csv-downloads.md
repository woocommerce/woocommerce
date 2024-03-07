---
post_title: How to add columns to analytics reports and CSV downloads
menu_title: Add columns to analytics reports
tags: how-to
---

Adding columns to analytics reports are a really interesting way to add functionality to WooCommerce. New data can be consumed in the table view of the user interface and in your user's favourite spreadsheet or third party application by generating a CSV.

These instructions assume that you have a test plugin for WooCommerce installed and activated. You can follow the ["Getting started" instructions](extending-woocommerce-admin-reports.md) to get a test plugin set up. That post also includes instructions to further modify the query that is executed to get the data in an advanced fashion - it isn't required to just add a simple column.

In WooCommerce, analytics CSVs are generated in two different ways: in the web browser using data already downloaded, or on the server using a new query. It uses the size of the data set to determine the method - if there is more than one page worth of results it generates the data on the server and emails a link to the user, but if the results fit on one page the data is generated and downloaded straight away in the browser.

We'll look at the on-server method for adding a column first, because this is also where the data sent to the browser is generated.

This example extends the Downloads analytics report. To get some data in your system for this report, create a downloadable product with a download expiry value, create an order purchasing the product, then download the product several times. In testing I created 26 downloads, which is enough that the report is spread over two pages when showing 25 items per page, and on a single page when showing 50 items per page. This let me test CSVs generated both on the server and in browser.

In the PHP for your plugin, add three filter handlers:

```php
// This adds the SELECT fragment to the report SQL
function add_access_expires_select( $report_columns, $context, $table_name ) {
	if ( $context !== 'downloads' ) {
		return $report_columns;
	}
	$report_columns['access_expires'] =
		'product_permissions.access_expires AS access_expires';
	return $report_columns;
}
add_filter( 'woocommerce_admin_report_columns', 'add_access_expires_select', 10, 3 );

// This adds the column header to the CSV
function add_column_header( $export_columns ) {
	$export_columns['access_expires'] = 'Access expires';
	return $export_columns;
}
add_filter( 'woocommerce_filter_downloads_export_columns', 'add_column_header' );

// This maps the queried item to the export item
function map_access_expires( $export_item, $item ) {
	$export_item['access_expires'] = $item['access_expires'];
	return $export_item;
}
add_filter( 'woocommerce_report_downloads_prepare_export_item', 'map_access_expires', 10, 2 );
```

This adds the access expiry timestamp to the Downloads table/CSV (when the CSV is generated on the server).

These three filters together add the new column to the database query, adds the new header to the CSV, and maps the data returned from the database to the CSV. The first filter `woocommerce_admin_report_columns` adds a SQL fragment to the `SELECT` statement generated for the data query. The second filter `woocommerce_filter_downloads_export_columns` adds the column header to the CSV generated on the server. The third filter `woocommerce_report_downloads_prepare_export_item` maps the value in the data returned from the database query `$item` to the export item for the CSV.

To finish this off by adding support for columns generated in browser, another filter needs to be added to your plugin's JavaScript:

```js
import { addFilter } from "@wordpress/hooks";
function addAccessExpiresToDownloadsReport(reportTableData) {
  const { endpoint, items } = reportTableData;
  if ("downloads" !== endpoint) {
    return reportTableData;
  }

  reportTableData.headers = [
    ...reportTableData.headers,
    {
      label: "Access expires",
      key: "access_expires",
    },
  ];
  reportTableData.rows = reportTableData.rows.map((row, index) => {
    const item = items.data[index];
    const newRow = [
      ...row,
      {
        display: item.access_expires,
        value: item.access_expires,
      },
    ];
    return newRow;
  });

  return reportTableData;
}
addFilter(
  "woocommerce_admin_report_table",
  "dev-blog-example",
  addAccessExpiresToDownloadsReport
);
```

This filter first adds the header to the CSV, then maps the data.

With the plugin you've created, you should now be able to add data to the analytics table, the CSV generated on the server, and the CSV generated on the browser.
