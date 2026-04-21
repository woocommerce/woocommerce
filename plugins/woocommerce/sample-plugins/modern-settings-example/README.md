# Modernised Settings SDK — Example Plugin

A minimal, installable demonstration of the [WooCommerce Modernised Settings SDK](../../../../docs/extensions/settings-and-config/modernised-settings-sdk.md) introduced in WooCommerce 10.8.

## What it does

- Registers a **Modern Example** tab under **WooCommerce → Settings**.
- Ships only natively-supported field types (`text`, `select`, `toggle`, `checkbox`) so it renders end-to-end with no JavaScript bundle.
- Doubles as the reference fixture for the SDK's flag-off zero-change guarantee: with the `modern-settings` flag off, the same tab renders via the legacy form with no behavioural difference.

## Install

1. Copy this directory to `wp-content/plugins/modern-settings-example/`.
2. Activate **WooCommerce Modernised Settings Example** from **Plugins** in `wp-admin`.
3. Enable the `modern-settings` feature flag. Two ways:

    - **WooCommerce Beta Tester** (recommended): install the plugin, then go to **Tools → WooCommerce → Beta tester → Features** and toggle **Modern Settings** on.
    - **Programmatically**: drop the snippet below into a mu-plugin.

      ```php
      <?php
      add_filter(
          'woocommerce_admin_features',
          static function ( array $features ): array {
              $features[] = 'modern-settings';
              return $features;
          }
      );
      ```

4. Visit **WooCommerce → Settings → Modern Example**.

With the flag on, the tab renders via the React `DataForm` path. Toggle the flag off and reload to see the same tab fall back to the legacy form.

## Layout

```text
modern-settings-example/
├── README.md
├── modern-settings-example.php
└── includes/
    └── class-modern-settings-example-tab.php
```

## Extending the example

To add a custom field type to this plugin, follow the [Registering custom field types](../../../../docs/extensions/settings-and-config/registering-custom-field-types.md) walkthrough. The end-to-end `currency` field example in that document is designed to drop into this plugin layout.

## Reference

- [Modernised settings SDK](../../../../docs/extensions/settings-and-config/modernised-settings-sdk.md) — full developer guide.
- [Registering custom field types](../../../../docs/extensions/settings-and-config/registering-custom-field-types.md) — when the native field set isn't enough.
- [`plugins/woocommerce/src/Internal/Admin/Settings/README.md`](../../src/Internal/Admin/Settings/README.md) — in-repo quick reference for engineers working on the SDK itself.
