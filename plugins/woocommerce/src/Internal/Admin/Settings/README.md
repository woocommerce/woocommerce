# React Settings Screens

> **For published developer documentation, see:**
>
> - [Modernised settings SDK](../../../../../../docs/extensions/settings-and-config/modernised-settings-sdk.md)
> - [Registering custom field types](../../../../../../docs/extensions/settings-and-config/registering-custom-field-types.md)
>
> The remainder of this README is the in-repo quick reference for engineers working in this directory.

This document explains how the React settings system renders settings pages by default, how to opt out, and how to extend supported field types.

## Overview

React settings render for a `WC_Settings_Page` subclass when **all** of these are true:

1. The `modern-settings` feature flag is enabled.
2. The page opts in by returning a non-null `ReactSettingsPageInterface` from `WC_Settings_Page::get_react_settings_page()` (base default is `null`).
3. No unsupported field types are detected in the section.
4. The `woocommerce_react_settings_opt_out` filter doesn't veto the render.

## Server-side hooks

### Opt out a settings page

Use this filter to prevent React rendering for a specific tab/section (surviving filter; useful for third-party runtime vetoes):

```php
add_filter(
    'woocommerce_react_settings_opt_out',
    function( $opt_out, $tab, $section, $settings, $settings_page ) {
        if ( 'my_tab' === $tab && 'my_section' === $section ) {
            return true;
        }
        return $opt_out;
    },
    10,
    5
);
```

### Extend supported field types and type mappings

Pages participate in the modernised renderer by returning an instance of `ReactSettingsPageInterface` from `WC_Settings_Page::get_react_settings_page()`. The interface exposes three extension points:

```php
use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;

final class MyReactSettingsPage implements ReactSettingsPageInterface {
    public function get_extra_type_map( string $section ): array {
        // Map a custom WooCommerce field type to a normalized renderer type.
        return array( 'hello_text' => 'text' );
    }

    public function get_extra_supported_types( string $section ): array {
        // Declare a custom renderer type that ships its own JS transformer.
        return array( 'hello_text' );
    }

    public function get_field_options( string $field_id, array $field, string $section ): ?array {
        // Return null to let inline options pass through; return an array to override.
        return null;
    }
}
```

The per-tab `WC_Settings_Page` subclass then returns this implementation:

```php
public function get_react_settings_page(): ?ReactSettingsPageInterface {
    return wc_get_container()->get( MyReactSettingsPage::class );
}
```

The `woocommerce_react_settings_supported_types`, `woocommerce_react_settings_type_map`, and `woocommerce_react_settings_field_options` filters were **removed in 10.8.0** in favour of this interface — they did not participate in any third-party API contract. The `woocommerce_react_settings_opt_out` filter (above) is retained.

## Client-side registry

React mounts on DOM nodes marked with `data-wc-modern-settings`. You can register per-screen overrides and custom field type transformers.

### Override a screen config

```js
window.wcReactSettings?.overrideScreen( 'general', '', {
    fieldTransformer: ( field ) => ( { ...field } ),
    rowConfigurations: {},
} );
```

### Register a field type transformer

```js
window.wcReactSettings?.registerFieldTypeTransformer(
    'hello_text',
    ( setting, baseField ) => ( {
        ...baseField,
        type: 'text',
    } )
);
```

## Mount ID format

React settings mount IDs are standardized:

```text
wc_settings_react_{tab}_{section}
```

Use `default` for the empty section, e.g. `wc_settings_react_products_default`.

## Example plugin

A minimal installable example lives in [`plugins/woocommerce/sample-plugins/modern-settings-example/`](../../../../sample-plugins/modern-settings-example/). It uses only natively-supported field types, so it renders end-to-end with no JavaScript bundle.
