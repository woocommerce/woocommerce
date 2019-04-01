=== WooCommerce Admin ===
Contributors: automattic
Tags: ecommerce, e-commerce, store, sales, reports, analytics, dashboard, activity, notices, insights, stats, woo commerce, woocommerce
Requires at least: 5.0.0
Tested up to: 5.1.1
Requires PHP: 5.2.4
Stable tag: 1.0.0
License: GPLv2
License URI: https://github.com/woocommerce/woocommerce-admin/blob/master/LICENSE.md

== Description ==

WooCommerce Admin is a new JavaScript-driven interface for managing your store. The plugin includes new and improved reports and a dashboard to monitor key metrics of your site.

**BETA SOFTWARE**

This plugin is under active development and, as such, we advise you to first install WooCommerce Admin in a staging/test environment. While this code is being run in production on sites, we recommend you first verify it works for you in a test environment, the same as any plugin for WooCommerce.

**New Reports for WooCommerce**

WooCommerce Admin has a host of new reports that are optimized for speed and have advanced filters that allow you to dive into data about your store:

- Revenue Report
- Orders Report 
- Products Report
- Categories Report
- Coupons Report
- Taxes Report
- Downloads Report
- Stock Report
- Customers Report

**Customizable Dashboard**

WooCommerce Admin also allows store owners to customize a new dashboard screen with “performance indicators” that correspond in importance to their store’s operation.

== Getting Started ==

Once again: This plugin is under active development and, as such, we advise you to first install WooCommerce Admin in a staging/test environment. While this code is being run in production on sites, we recommend you first verify it works for you in a test environment, the same as any plugin for WooCommerce.

= Minimum Requirements =

* WordPress 5.0
* WooCommerce 3.5.1 or greater
* PHP version 5.2.4 or greater. PHP 7.2 or greater is recommended
* MySQL version 5.0 or greater. MySQL 5.6 or greater is recommended

Visit the [WooCommerce server requirements documentation](https://docs.woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option, as WordPress handles the file transfers and you don't need to leave your web browser. To perform an automatic install: 
1. Log in to your WordPress dashboard.
2. Go to: Plugins > Add New.
3. Enter “WooCommerce Admin” in the Search field, and view details about its point release, the rating and description. 
4. Select “Install Now” when you’re ready

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your web server via your favorite FTP application. The WordPress Codex contains instructions at [Manual Plugin Installation](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Where can I report bugs or contribute to the project? =

Bugs should be reported in the [WooCommerce Admin GitHub repository](https://github.com/woocommerce/woocommerce-admin/).

= This is awesome! Can I contribute? =

Yes, you can! Join our [GitHub repository](https://github.com/woocommerce/woocommerce-admin/)

Release and roadmap notes are available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/2019/01/15/woocommerce-blocks-1-3-0-release-notes/)

== Screenshots ==

1. WooCommerce Admin Dashboard
2. Activity Panels
3. Analytics

== Changelog ==

= 0.8.0 - 2019-02-28 =

- Table Component: Reset search on compare
- MenuItem Component: Added isCheckBox prop
- a11y: Enhancements to “Rows per Table” on the dashboard
- Taxes Report: Fix comparison mode and segmentation labels
- Fix css linter errors
- Test Framework: Require WordPress 5.0 or greater to run phpunit
- Table Component: Fix search positioning in small viewports
- Chart Component: Preserve chart colors bug fix
- Navigation: Close wp-admin menu when navigating between reports in narrow viewports
- Analytics: Don’t show variation table when in an active search
- Dashboard: Fix for style of summary number placeholders
- Downloads/Customers Report: Fix for filters
- Product Variation name format now matches Woo core
- phpcs fixes in unit tests
- Chart: Mouse pointer now displayed in entire clickable area
- Analytics: Sort tabular data when selecting a Summary Number
- Chart: Fixes for the legend totals
- Build: Move IE11 css to specific css file
- Downloads Report: Fix for sorting bug
- Stock Report: fix the product link
- Chart: Refactor of d3 logic
- Autocompleter: Increase debounce time to reduce API requests
- Segmented Selection Component: fix for missing borders
- Chart: Add messaging when no data is available for selected filters
- Setup: Improvements to install flow