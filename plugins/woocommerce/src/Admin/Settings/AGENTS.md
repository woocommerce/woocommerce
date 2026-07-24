# Settings UI

These classes power the Settings UI renderer for classic `wc-settings` pages. They are autoloaded modern code, but they are called from legacy code (`includes/admin/settings/`, `includes/admin/views/html-admin-settings.php`) that runs on every settings request.

## Mid-update fatal guard (required for every change)

During a plugin update, a request can pair new files on disk with stale cached classes (or the reverse). The settings surface renders wp-admin, so one unguarded call in that window white-screens the settings page. The 10.9.1 release was reverted on WP Cloud for exactly this bug class.

Rules for this package and its call sites:

- Code reachable from `includes/`, templates, or hook callbacks must call `SettingsUIRequestContext` (and the other classes here) either through methods that exist since WooCommerce 10.9, or inside a `class_exists` check plus `try/catch (\Throwable)` that falls back to legacy rendering. 10.9.x is the oldest release shipping these classes, so it is the version a stale class can be. `is_drill_down()` and `get_settings_page()` do not exist in 10.9; the rest of the context's surface does.
- Public and protected method signatures are additive only. Never remove or change one; deprecate and keep it working.
- Never delete or rename a class file that has shipped in a release. A stale classmap entry pointing at a missing file fatals inside the autoloader, where no guard can catch it.
- Never add a required method to an interface here. Stale implementers fail at class-link time, uncatchably. Add a concrete default to the `SettingsSection` base class instead, the way `SettingsSectionUIPageProviderInterface` was introduced.

Guarded call sites to copy from: `includes/admin/views/html-admin-settings.php`, `WC_Settings_Page::add_settings_ui_body_class()`, `Settings::add_settings_ui_schema()`, `WCAdminAssets::get_settings_ui_script_dependencies()`.
