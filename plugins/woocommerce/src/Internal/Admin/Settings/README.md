# React Settings Screens

This document explains how to add a new settings screen to the React settings system.

## Overview

Adding a new screen requires changes on both the server and client:

- **Server**: register the screen so settings are preloaded into `wcSettings`.
- **Client**: register the screen so the React page mounts on the standard DOM ID.

The React UI only renders when the `react-settings` feature flag is enabled and the
screen’s settings types are supported.

## Server-side registry

1. Add a new entry in `ReactSettingsRegistry::get_entries()`:

    - `tab`: the settings tab slug (e.g. `products`)
    - `section`: section slug (use `''` for the default section)
    - `schema`: schema class for transforming settings
    - `restPath`: REST route used to build the request passed to the schema
    - `payloadPath`: where the transformed response is stored in `wcSettings`
    - `supportedTypes` and `typeMap`: the allowed types and type normalization

2. Ensure the schema supports any special field types:

    - Normalize WooCommerce types to supported types in the schema.
    - Provide options for select fields that are not defined in settings.

## Client-side registry

1. Add a new entry in `client/admin/client/settings/react-settings-registry.tsx`:

    - `dataPath`: path to the preloaded data (mirrors the server `payloadPath`)
    - `mountId`: standard mount ID in the DOM
    - `fieldTransformer`: field transformer for your screen
    - `rowConfigurations`: optional layout config

2. If the screen needs custom layout or visibility rules, add a config file
   similar to `settings-general/react-settings-config.ts`.

## Mount ID format

React settings mount IDs are standardized:

```text
wc_settings_react_{tab}_{section}
```

Use `default` for the empty section, e.g. `wc_settings_react_products_default`.

## Example: Products → Inventory

Server entry:

```php
'products.inventory' => array(
    'tab'            => 'products',
    'section'        => 'inventory',
    'schema'         => ProductSettingsSchema::class,
    'restPath'       => '/wc/v4/settings/products',
    'payloadPath'    => array( 'settings', 'products', 'inventory' ),
    'supportedTypes' => array( 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'toggle' ),
    'typeMap'        => array(),
),
```

Client entry:

```tsx
{
    id: 'products.inventory',
    dataPath: [ 'settings', 'products', 'inventory' ],
    mountId: 'wc_settings_react_products_inventory',
    className: 'woocommerce-settings-products',
    fieldTransformer: defaultFieldTransformer,
},
```
