# WooCommerce Admin

The WooCommerce Admin plugin is where the next iteration of the administrative experience for WooCommerce is being developed. The project is being built on top of the modern JavaScript packages that are being released from the [core WordPress editor project](https://github.com/wordpress/gutenberg). By building with these `@wordpress/components` - WooCommerce Admin seeks to create a cohesive experience with the latest WordPress core design system, and to deliver a fast and modern set of tools to manage one's WooCommerce Store with.

Currently, development efforts have been focused on two primary areas:

- Analytics: A new suite of robust reports with powerful filtering and data export utilities.
- Dashboard: A fully customizable view into the metrics that matter most to your store.
- Navigation / Activity Panels: A way to keep in touch with key jobs to be done on your store. The new Activity panels in the header section of your store highlight areas that need attention.

## Analytics

With WooCommerce Admin installed, a new Analytics menu item is created in the wp-admin menu system. This menu item, and the reports contained insde of it are available to all wp-admin users that have the `view_woocommerce_reports` capability, so per a standard WooCommerce install this would give `shop_manager`s and `administrator`s access to the reports.

Each report is quite unique with its own set of filtering options and chart types. To learn more about each individual report, please view the pages below:

- [Analytics Basics](analytics-basics.md)
- [Revenue Report](analytics-revenue-report.md)
- [Orders Report](analytics-orders-report.md)
- [Products Report](analytics-products-report.md)
- [Categories Report](analytics-categories-report.md)
- [Coupons Report](analytics-coupons-report.md)
- [Taxes Report](analytics-taxes-report.md)
- [Downloads Report](analytics-downloads-report.md)
- [Stock Report](analytics-stock-report.md)
- [Customers Report](analytics-customers-report.md)

### Settings and Historical Data Import

The Analytics section also has a menu item for _Settings_. On this page you can control items that affect how the WooCommerce Admin Analytics system works, and you can also import historical data into the reporting database tables.

- [Analytics settings](analytics-settings.md)
- [Historical Data Import](analytics-historical-data-import.md)
