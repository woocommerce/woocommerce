=== WooCommerce Admin ===
Contributors: automattic
Tags: ecommerce, e-commerce, store, sales, reports, analytics, dashboard, activity, notices, insights, stats, woo commerce, woocommerce
Requires at least: 5.0.0
Tested up to: 5.1.1
Requires PHP: 5.4.0
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
* WooCommerce 3.6.0 or greater
* PHP version 5.4 or greater. PHP 7.2 or greater is recommended
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

Release and roadmap notes are available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/category/wc-admin/)

== Screenshots ==

1. WooCommerce Admin Dashboard
2. Activity Panels
3. Analytics

== Changelog ==

= 0.12.0 2019-05-14

- Fix: dashboard issues #2194
- Fix: Dashboard: re-arrange section dropdown order #2216
- Fix: Refactor page handler API #2209
- Fix: EllipsisMenu misalignment in wide containers #2207
- Dev: Handle “all guests” when getting customers from orders. #2038
- Dev: Order milestones: prevent order count queries from being issues on every request. #2224
- Dev: Replace Rebuild reports settings with Import Historical Data #2198
- Dev: add a defaultValue parameter to the ReportFilters component #2150 👏 @rrennick
- Dev: Record refunds separate from order entries #2083
- Dev: Add refund filtering to order reports #2179
- Dev: Hook up API Historical Data screen to API endpoints #2177
- Fix: Show Y grid ticks/lines in charts where all values are lower than 1 #2147
- Fix: Dashboard: Rename class/feature to remove conflict with legacy dashboard widget #2138
- Fix: add defaultOrder field to tablecard header #2139 👏 @rrennick
- Fix: Travis: Exit scripts with error when one occurs #2184
- Dev: Add profile wizard base page #1933
- Dev: Save section/block preferences in user data #2148
- Fix: Vertically center Dashboard 'Add more' button #2171
- Fix: Don't make an API call when there are no performance indicators #2157
- Dev: Add Historical Data Import Screen #2073
- Fix: Fix customer type filter param in orders report #2169
- Dev: EllipsisMenu: Refactor children to renderContent #2154
- Dev: Dashboard: Section add, remove, move up, and move down #2134
- Fix: Persist chart interval in Dashboard #2156
- Dev: Allow renaming Dashboard sections #2131
- Dev: Add import endpoint and controller #2034
- Fix: Prevent chart type buttons breaking in two lines #2135
- Dev: Support Sequential Order Numbers in Downloads Report order number filter #1907
- Dev: Scroll to top of the table when navigating table pages #2051
- Dev: Add empty state for the Reviews panels #2124

== Changelog ==

= 0.11.0 2019-04-17

- Dev: Extend report submenu items #2033
- Dev: Extension Examples #2018
- Dev: Update admin notes readme #2043
- Dev: Add profile wizard base page #1933
- Dev: Method to publish core ready features #1863
- Dev: Add PHP autofix to pre-commit hook #2022
- Dev: PHP Lint fixes #2023
- Dev: Update Link in readme to Proper Location #2026
- Dev: Split D3Chart utils/axis file #2000
- Dev: Remove coupon_total from order stats table #1975
- Fix: Transpile acorn-jsx package #2064
- Fix: Inbox: Giving Feedback Notice #2019 👏 @Mr-AjayM
- Fix: Check if welcome message notice exists before creating it #2063
- Fix: Respect manual offsets in default before/after params #2035
- Fix: Fix wrong dates in Reviews and Orders panels of the Activity Panel #2013
- Fix: Make customer name extendable #1976
- Fix: center mobile icon vertically #1990 👏 @rrennick
- Fix: search filter focus #1994
- Fix: Fix date filter layout on Android #1996
- Fix: Order Status filter: <select> is not vertically aligned #1971 👏 @dinhtungdu
- Fix: add filter length condition to enable filter check #1983 👏 @rrennick
- Fix: lookup product title in order meta if product has been deleted #1977 👏 @rrennick
- Fix: Cast order totals as floats when calculating net total #1945
- Enhancement: Add empty state in Stock panel #2049
- Enhancement: Add empty state in Orders panel #2037
- Enhancement: Hook up Stock panel #1998
- Enhancement: Add order milestone notifications. #1872
- Enhancement: Add leaderboard controller and endpoint #1995
- Enhancement: Hook up leaderboards endpoint to dashboard #2004
- Enhancement: Activity Panel: Add a mobile app note #2008
- Enhancement: Scroll to top only when URL changes #1989
- Enhancement: Allow negative values in charts #1979

= 0.10.0 2019-04-02

- Dev: Properly namespaced methods in wc-admin.php. props @ronakganatra9 #1804
- Dev: Changed text-domain to `woocommerce-admin` #1795
- Dev: Updates to plugin header #1764
- Dev: Clear scheduled actions on plugin deactivate #1715
- Dev: Refactor getFilterQuery #1873
- Dev: Update npm dependencies #1913
- Fix: ESLint error in leaderboards #1914
- Fix: REST API Requests failing with isUnboundedRequest #1954
- Fix: Bugs in Reviews panel #1947
- Fix: Scroll to top of page when changing reports #1958
- Fix: Update variations controller filter names #1949
- Fix: Sku sorting in variations report #1952 #1801 props @ronakganatra9
- Fix: REST API customers exact match filters
- Fix: EmptyContent on narrow viewports #1703
- Fix: Empty chart and summary numbers in Categories Report #1698
- Fix: Display chart and summary numbers placeholders when loading search terms #1694
- Fix: Display order number on reports #1706
- Fix: Missing Date Picker Calendar in RTL #1810
- Fix: Circles on chart hover #1816
- Fix: Date Range dropdown for RTL locales #1803
- Fix: Sorting by tax_code in Taxes Report #1811
- Fix: Charts in RTL locales #1794
- Fix: RTL css #1781
- Fix: CSS Lint issues in store-alerts #1793
- Fix: Products stats segmentation when filtering categories #1779
- Fix: Remove timezone from appendTimestamp() output #1778
- Fix: REST API Make variations and coupons endpoints return zero-value items #1765
- Fix: REST API make categories return zero-value items #1755
- Fix: REST API Make products and rates endpoints return zero-value items #1722
- Fix: Taxes report, sort taxes numerically #1766
- Fix: Chart comparison mode single category #1762
- Fix: Simplify options in filters #1748
- Fix: Show compared keys in chart legends even if their values are 0 #1754
- Fix: IE11 Leaderboard issue
- Fix: Use IDs instead of labels to identify chart legend items #1730
- Fix: Update table columns visibility when loading finishes #1729
- Fix: Update chart colors #1731
- Fix: Date Picker css tabs #1717
- Fix: Infinite loop in customer population #1936
- Fix: PHP notices on orders data store #1870
- Fix: Table links to internal reports #1824
- Accessibility: Add button focus style to report chart interval selector #1740
- Enhancement: Add "Cancel All" method to ActionScheduler Store #1813
- Enhancement: Add store notice for processing historical data #1763
- Enhancement: Ensure other scheduled actions are claimed before WC Admin actions #1759
- Enhancement: Load on hold reviews in the Activity Panel: #1871
- Enhancement: Add overlay to Product Placeholder image #1880
- Enhancement: Added "Remind me later" ability to StoreAlert component #1848
- Enhancement: Set reviews Activity Panel unread indicator based off REST API data #1824
- Enhancement: Set orders Activity Panel unread indicator based off REST API data #1860
- Enhancement: Hide Reviews Activity Panel if reviews are disabled #1852
- Enhancement: Lookup product title in order meta if product has been deleted #1977
- Performance: Avoid un-needed chart re-renders #1780
- Performance: Don't run lookup table historical population on activation of plugin #1741
- Performance: Don't dispatch REST API requests when window/tab is hidden #1732
- Performance: Only check for unsnooze note on admin_init #1960

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
