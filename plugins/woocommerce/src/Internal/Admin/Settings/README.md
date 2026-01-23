# React Settings Screens

This document explains how the React settings system renders settings pages by default, how to opt out, and how to extend supported field types.

## Overview

React settings render automatically for all WooCommerce settings pages when the `modern-settings` feature flag is enabled. Rendering falls back to legacy settings when unsupported field types are detected or when a page opts out.

## Server-side hooks

### Opt out a settings page

Use this filter to prevent React rendering for a specific tab/section:

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

### Register supported field types and type mappings

Use these filters to expand supported React field types or normalize custom WooCommerce field types:

```php
add_filter(
    'woocommerce_react_settings_supported_types',
    function( $types, $tab, $section ) {
        $types[] = 'hello_text';
        return $types;
    },
    10,
    3
);

add_filter(
    'woocommerce_react_settings_type_map',
    function( $map, $tab, $section ) {
        $map['hello_text'] = 'hello_text';
        return $map;
    },
    10,
    3
);
```

## Client-side registry

React mounts on DOM nodes marked with `data-wc-settings-react`. You can register per-screen overrides and custom field type transformers.

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
