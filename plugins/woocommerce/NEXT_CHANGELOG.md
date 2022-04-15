# Changelog

## [6.5](https://github.com/woocommerce/woocommerce/releases/tag/6.5) - 2022-04-15 

-   Minor - Added a temporary filter to patch the WCA JS packages i18n json files #32603
-   Patch - Enable the "Save changes" button within the variations panel when a textfield receives input.
-   Patch - Ensure that an existing order with auto-draft status won't be interpreted as pending when determining if the status has changed.
-   Patch - Fixing bug in which tasks reminder bar was displayed on product screens
-   Patch - Fix issue where some tasks where not being tracked as completed, when tracking is enabled. #32493
-   Patch - Fix WooCommerce Payments task not showing up in some supported countries. #32496
-   Minor - Remove Pinterest extension from OBW #32626
-   Patch - Revert back menu position to floats as string for WP compatibility.
-   Minor - String sorting when using different locales.
-   Minor - WCPayments task is not visible after installing the plugin #32506
-   Minor - Add E2E tests to disabled welcome modal #32505
-   Minor - Add Pinterest extension to onboarding wizard and marketing task #32527
-   Minor - Add `order_item_display_meta` option to orders endpoint (REST API), to osupport filtering out variation meta.
-   Minor - Generic migration support for migration from posts + postsmeta table to any custom table. Additionaly, implement migrations to various COT tables using this generic support.
-   Minor - Make it possible for downloadable files to be in an enabled or disabled state.
-   Patch - Adds Other payment methods link to the payment setting page when the store is located in WC Payments eligible country.
-   Minor - Merge WCA install routines to the core
-   Minor - Remove load_plugin_textdomain method from admin plugin.
-   Patch - Simplify the WooCommerce Admin init routine. #32489
-   Minor - UI changes for set up payments task
-   Minor - Update payment gateway logic in payment task
-   Patch - Update payment method link to the internal extension marketplace
-   Patch - Update WCA deactivation hooks to work with WC deactvation.
-   Patch - Updating deasync package to resolve install script issue with Linux
-   Minor - Add tracking for block themes.
-   Minor - Fix husky git hooks.
-   Patch - Move feature flag config files to Woocommerce plugin to support unit test execution in the wp-env environment.
-   Patch - Pass `WC_ADMIN_PHASE=core` to build commands & remove "plugin" env
-   Patch - Avoid unsupported operand type errors from within `WC_Admin_Post_Types::set_new_price()`.
-   Patch - Changelog entry not needed, because this is an adjustment to unreleased code.
-   Minor - Remove custom user-agent from featured extensions requests.
-   Patch - Update progress header bar styles in task list #32498
-   Patch - WC_Product_CSV_Importer_Controller::is_file_valid_csv now just invokes wc_is_file_valid_csv. #32460
-   Minor - Add a context param with a default value of global to Admin\Notes\DataStore::get_notes(), get_notes_count(), and get_notes_where_clauses(). #32574
-   Minor - Add new sectioned task list component. #32302
-   Patch - Add woocommerce_download_parse_remote_file_path and woocommerce_download_parse_file_path filters to modify the parse_file_path method file_path return. #32317

---

[See changelogs for previous versions](https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/changelog.txt).
