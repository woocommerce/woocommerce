---
post_title: Modernised settings SDK (10.8)
sidebar_label: Modernised settings SDK
sidebar_position: 5
---

# Modernised settings SDK

The Modernised Settings SDK is an experimental, opt-in path that renders existing `WC_Settings_Page` subclasses with the React-based `DataForm` component instead of the legacy PHP `WC_Admin_Settings::output_fields()` renderer. It is shipped in WooCommerce 10.8 as the first user-facing slice of a longer-term effort to bring WooCommerce settings onto the same component model used elsewhere in `wp-admin`.

This guide covers everything an extension author needs to opt a settings page in, register custom field types, and understand the fallback behaviour.

## Status in 10.8

- The SDK is **experimental** and ships behind the `modern-settings` feature flag, which is **off by default**.
- No Core settings page is opted in to the modern path in 10.8. The flag is intentionally a tool for extensions and integrators to experiment with against their own pages.
- The legacy save POST handler still runs for every settings page, regardless of the flag. Saves continue to flow through `WC_Admin_Settings::save_fields()` and the existing `woocommerce_update_options_*` hooks. The only thing the modern path replaces in 10.8 is the **render** of the form.
- With the flag off, no behaviour changes anywhere — even on pages that have already set `$is_modern = true`.

## Enabling the feature flag for development

There are two ways to turn the flag on while you develop or test against the modern path.

### Via the WooCommerce Beta Tester plugin

1. Install and activate the [WooCommerce Beta Tester](https://github.com/woocommerce/woocommerce-beta-tester) plugin.
2. Navigate to **Tools → WooCommerce → Beta tester → Features**.
3. Toggle **Modern Settings** on.

### Programmatically

Add a small mu-plugin or snippet to your development site:

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

This is useful in CI or when scripting a wp-env environment.

## Opting a settings page in (`$is_modern`)

A `WC_Settings_Page` subclass opts in to the modern path by setting the `$is_modern` property to `true`:

```php
<?php
defined( 'ABSPATH' ) || exit;

class My_Plugin_Settings_Tab extends WC_Settings_Page {
    /**
     * @var bool
     */
    protected $is_modern = true;

    public function __construct() {
        $this->id    = 'my_plugin';
        $this->label = __( 'My Plugin', 'my-plugin' );
        parent::__construct();
    }

    protected function get_settings_for_default_section(): array {
        return array(
            array(
                'title' => __( 'My Plugin settings', 'my-plugin' ),
                'type'  => 'title',
                'id'    => 'my_plugin_options',
            ),
            array(
                'title'   => __( 'API key', 'my-plugin' ),
                'id'      => 'my_plugin_api_key',
                'type'    => 'text',
                'default' => '',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'my_plugin_options',
            ),
        );
    }
}
```

Two independent things happen when `$is_modern` is `true`:

1. The full React **settings shell** (rolled out behind the separate `settings` feature flag) treats the tab as a first-class React surface rather than embedding the legacy form.
2. The per-page **render path** in `WC_Settings_Page::output()` checks the `modern-settings` flag and, if it is on, attempts to mount the React renderer instead of calling `WC_Admin_Settings::output_fields()`.

You do not need to change the `save_*` methods on your subclass. The existing `woocommerce_settings_save_{tab}` action still fires and the legacy save handler is authoritative in 10.8.

## Public PHP API

The PHP surface for the modern path is the `ReactSettingsSchema` class plus three extension filters.

### `ReactSettingsSchema`

`Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema` exposes the following static methods. They are intended for use by extensions, even though the namespace is currently `Internal\*` — moving the class to a non-`Internal` namespace is tracked as a Phase 2 refactor and the method signatures will be preserved.

| Method | Purpose |
|--------|---------|
| `is_opted_out( $tab, $section, $settings, $page )` | Returns `true` if the `woocommerce_react_settings_opt_out` filter has opted the tab/section out. |
| `get_supported_types( $tab, $section, $settings, $page )` | Returns the normalized list of field types the React renderer can handle for the given tab/section. |
| `get_type_map( $tab, $section, $settings, $page )` | Returns the map of raw WooCommerce field types to the normalized types in the supported list. |
| `get_unsupported_fields( $tab, $section, $settings, $page )` | Returns an array of `{ id, type, normalized_type }` entries for any field that cannot be rendered. Empty when the page is fully supported. |
| `has_renderable_fields( $tab, $section, $settings, $page )` | Returns `true` if at least one field would render under the React path. |
| `build_response( $tab, $section, $settings, $page )` | Transforms the legacy settings-definition array into the payload the React renderer consumes (`{ id, title, description, values, groups }`). |
| `get_payload_path( $tab, $section )` | Returns the dot-path under `window.wcSettings.admin` where the payload is published, e.g. `[ 'settings', 'general', 'default' ]`. |
| `get_mount_id( $tab, $section )` | Returns the DOM id of the mount element, e.g. `wc_settings_react_general_default`. |

All methods accept the empty string `''` for `$section` to mean "default section" and normalize it internally to `default`.

### Filters

#### `woocommerce_react_settings_opt_out`

Force a specific tab/section to fall back to the legacy renderer even when the flag is on.

```php
add_filter(
    'woocommerce_react_settings_opt_out',
    static function ( bool $opt_out, string $tab, string $section, array $settings, $settings_page ): bool {
        if ( 'my_plugin' === $tab && 'advanced' === $section ) {
            return true;
        }
        return $opt_out;
    },
    10,
    5
);
```

#### `woocommerce_react_settings_supported_types`

Add to (or remove from) the list of normalized field types the renderer accepts. Use this when you have registered a custom JS Edit component and want a raw type to be considered renderable.

```php
add_filter(
    'woocommerce_react_settings_supported_types',
    static function ( array $types, string $tab, string $section ): array {
        if ( 'my_plugin' === $tab ) {
            $types[] = 'currency';
        }
        return $types;
    },
    10,
    3
);
```

#### `woocommerce_react_settings_type_map`

Map a raw WooCommerce field type to a normalized type the renderer already supports. This is the simplest extension point for custom types that can be rendered as a primitive.

```php
add_filter(
    'woocommerce_react_settings_type_map',
    static function ( array $map, string $tab, string $section ): array {
        $map['currency'] = 'text';
        return $map;
    },
    10,
    3
);
```

For the trade-offs between these two filters and the JS-side registration, see [Registering custom field types](./registering-custom-field-types.md).

## Mount protocol

When a page resolves to the modern path, `WC_Settings_Page::output()` emits a single `<div>` and returns:

```html
<div id="wc_settings_react_my_plugin_default"
     data-wc-modern-settings="1"
     data-wc-settings-tab="my_plugin"
     data-wc-settings-section=""></div>
```

- The `id` attribute is the value returned by `ReactSettingsSchema::get_mount_id( $tab, $section )` and follows the format `wc_settings_react_{tab}_{section}`. The empty section is normalized to `default`.
- `data-wc-modern-settings="1"` is the marker the JS bootstrapper scans for.
- `data-wc-settings-tab` and `data-wc-settings-section` carry the tab and section ids verbatim (the section attribute is empty for the default section).

The settings payload itself is published on the page via `wp_add_inline_script`/`wp_localize_script` under a deterministic path:

```js
window.wcSettings.admin.settings[ tab ][ section || 'default' ] = {
    id: 'my_plugin',
    title: 'My Plugin',
    description: '',
    values: { my_plugin_api_key: '' },
    groups: {
        my_plugin_options: {
            title: 'My Plugin settings',
            description: '',
            order: 0,
            fields: [
                { id: 'my_plugin_api_key', label: 'API key', type: 'text', desc: '' },
            ],
        },
    },
};
```

The exact payload shape is what `ReactSettingsSchema::build_response()` returns. Treat the structure as the contract; the renderer reads only those keys.

## JS extension points

The runtime registry is exposed on `window.wcReactSettings`:

```js
window.wcReactSettings.registerFieldTypeTransformer(
    'currency',
    ( setting, baseField ) => ( {
        ...baseField,
        type: 'text',
    } )
);
```

- `setting` is the raw WooCommerce settings array entry (the same shape your `get_settings_for_*_section()` method returns).
- `baseField` is the partially-normalized field shape `{ id, label, type, description? }` produced by the schema layer.
- The function must return a [DataForm field](https://developer.wordpress.org/block-editor/reference-guides/data/data-core/) shape: at minimum `{ ...baseField, type }`, optionally with `Edit`, `getValue`, `setValue`, `isValid`, or `elements`.

Registrations are global and persist for the lifetime of the page. There is no deregister API in 10.8.

A typed npm package, `@woocommerce/modern-settings-sdk`, is planned to ship alongside the SDK. It will provide TypeScript types and helper utilities (`baseFieldTransformer`, `parseOptions`, `createChildrenWithRows`, `reorderGroupFields`). In 10.8 the registry is consumed via the `window.wcReactSettings` global; a dedicated `wc-modern-settings-sdk` script handle is planned for a follow-up release.

## Native field type coverage

Out of the box in 10.8, the renderer recognises the following raw WooCommerce field types and renders each one with a matching DataForm primitive or a custom Edit component:

| Type family | Raw type(s) | How it renders |
|-------------|-------------|----------------|
| Text-like | `text`, `password`, `email`, `url`, `tel` | Single-line input. `password` uses a masked input; `url` validates with `URL.canParse`; `tel` uses an `InputControl` with `type="tel"`. |
| Number | `number` | Integer input. |
| Choice | `select`, `radio`, `multiselect`, `single_select_page_with_search` | Standard select / radio / multi-select. The searchable variant uses `ComboboxControl`, with the page list synthesised via `get_pages()` when no `options` are provided. |
| Boolean | `checkbox`, `toggle` | Checkbox or toggle, persisted as `'yes'` / `'no'`. |
| Long text | `textarea` | `TextareaControl`. |
| Colour | `color` | `ColorPicker`. |
| Date / time | `date`, `datetime`, `datetime-local`, `month`, `week`, `time` | `DatePicker` / `DateTimePicker` for the first three; `InputControl` with the matching HTML5 `type` for the others. |
| Description | `info` | Read-only description row. The body comes from `setting['text']` (or `setting['desc']` if `text` is absent). The row never writes to `formData` — `info` rows must declare an explicit `id` to opt into the React renderer. |

The default type map normalizes a small set of WooCommerce-specific raw types onto the primitives above:

| Raw type | Normalized to |
|----------|---------------|
| `single_select_country` | `select` (options synthesised from `WC()->countries`) |
| `multi_select_countries` | `multiselect` (options synthesised from `WC()->countries`) |
| `single_select_page` | `select` |

Any raw type that is neither in the supported list nor present in the type map is reported as unsupported and triggers the fallback (see below).

## Fallback signals

The renderer falls back to the legacy `WC_Admin_Settings::output_fields()` path when any of the following are true:

1. The `modern-settings` feature flag is off (the React mount is never emitted).
2. The `woocommerce_react_settings_opt_out` filter returns `true`.
3. `ReactSettingsSchema::get_unsupported_fields()` returns at least one entry.
4. `ReactSettingsSchema::has_renderable_fields()` returns `false` (e.g. the section only contains `title`/`sectionend` markers).

When the fallback is triggered by an unsupported field type — i.e. the page would otherwise have rendered via React but for a single offending field — two developer signals fire:

1. A browser-console message identifying the tab, section, and the offending field ids and types. This always fires when the fallback runs and is intended as the primary developer signal.
2. A server-side `wc_doing_it_wrong` notice naming the same fields. As with all `wc_doing_it_wrong` calls, this is gated by `WP_DEBUG` and surfaces in the PHP error log when enabled.

Both signals are intended for developers, not end users. They do not surface anywhere a merchant would see them.

## Migration walkthrough

You have an existing `WC_Settings_Page` subclass. Here is how to adopt the modern path.

1. **Audit your field types.** Compare the `type` keys in your `get_settings_for_*_section()` arrays against the supported list and the default type map. Anything outside both will trigger a fallback.
2. **Set `$is_modern = true`** on your subclass. This is a no-op as long as the `modern-settings` flag is off, so it is safe to ship.
3. **Enable the `modern-settings` flag** in your dev environment (see [Enabling the feature flag for development](#enabling-the-feature-flag-for-development)).
4. **Visit your settings tab in `wp-admin`.** If a fallback fires, the browser console will print a message naming the offending field types (with a matching `wc_doing_it_wrong` notice in the PHP error log when `WP_DEBUG` is on).
5. **Resolve unsupported types.** You have three options:
    - Change the field's raw `type` to one already in the supported list.
    - Map your raw type to a primitive via the `woocommerce_react_settings_type_map` filter.
    - Register a custom field type via the JS extension point. See [Registering custom field types](./registering-custom-field-types.md).
6. **Smoke-test the save path.** The legacy save POST handler is still authoritative in 10.8, so saving should behave identically. Confirm the values you submit round-trip through `WC_Admin_Settings::get_option()`.

## Flag-off zero-change guarantee

When the `modern-settings` flag is off, the SDK is invisible:

- `WC_Settings_Page::output()` runs the legacy renderer unconditionally.
- No mount markup is emitted.
- No payload is published on `window.wcSettings`.
- Setting `$is_modern = true` on a subclass has no observable effect on the rendered page.

This guarantee is enforced by an automated end-to-end test that asserts the rendered DOM with the flag off matches the legacy output exactly.

## Example plugin

A complete, installable example lives under [`plugins/woocommerce/sample-plugins/modern-settings-example/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/sample-plugins/modern-settings-example). It registers a `Modern Example` tab under **WooCommerce → Settings**, opts in via `$is_modern = true`, and uses only natively-supported field types so it renders end-to-end with no JS bundle.

To try it locally:

1. Copy the `modern-settings-example/` directory into `wp-content/plugins/`.
2. Activate **WooCommerce Modernised Settings Example** from the Plugins screen.
3. Enable the `modern-settings` feature flag (see above).
4. Visit **WooCommerce → Settings → Modern Example**.

Toggling the flag off and reloading the same tab gives you the legacy form with no other change. That is the zero-change guarantee in action.

## Out of scope for 10.8

The following are explicitly **not** part of the 10.8 SDK and are tracked for follow-up phases:

- **REST-driven save.** The save path remains the legacy POST handler in 10.8.
- **Opting Core settings pages in.** No Core page sets `$is_modern = true` in 10.8. Phase 2 will pick a small surface (likely the General tab) once the SDK has been validated against extension pages.
- **Custom-component coverage** for `image_width`, `relative_date_selector`, and `slotfill_placeholder`. These raw types fall back today; native Edit components are planned.
- **`DataForm.Fields.extend()`** as an upstream WordPress SPI. The interim runtime registry on `window.wcReactSettings` will be deprecated once an upstream extension point is available.
