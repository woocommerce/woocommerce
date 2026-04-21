---
post_title: Registering custom field types (modern settings SDK)
sidebar_label: Custom field types
sidebar_position: 6
---

# Registering custom field types

This guide is a focused walkthrough for the case where your `WC_Settings_Page` subclass uses a setting `type` that is not in the [native supported set](./modernised-settings-sdk.md#native-field-type-coverage), but you want it to render on the modern path rather than fall back to the legacy renderer.

If you have not already, read the [Modernised settings SDK overview](./modernised-settings-sdk.md) first.

## When to register a custom field type

When the `modern-settings` flag is on, every field on every `WC_Settings_Page` subclass is checked against the supported types. A field whose normalized type is not supported triggers a fallback to the legacy renderer for the entire section.

You have three ways out of the fallback:

1. **Use a supported type.** If your field can be expressed as `text`, `select`, `checkbox`, etc., changing the raw `type` is the simplest fix.
2. **Wait for native support.** The list of native types is expected to grow each release. If your field is reasonably common, [open an issue](https://github.com/woocommerce/woocommerce/issues) before reaching for a custom registration.
3. **Register a custom field type.** Recommended when your setting needs a bespoke input (a currency picker, a code editor, a coordinate map, etc.) that the renderer cannot model with a primitive.

This document covers option 3.

## The PHP side

You need to tell the renderer that your raw field type is renderable. There are three options, in increasing order of customization.

### Option A — Map to a primitive

If your custom type is conceptually a primitive — for example, a currency input is just a `text` input with formatting — register it in the type map. No JS is required.

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

The renderer will treat `currency` fields as `text` fields end-to-end.

### Option B — Mark as supported and ship a JS Edit

If your field genuinely needs custom UI, declare the raw type as supported so it is not normalized away, and register a JS transformer to provide an `Edit` component.

```php
add_filter(
    'woocommerce_react_settings_supported_types',
    static function ( array $types, string $tab, string $section ): array {
        $types[] = 'currency';
        return $types;
    },
    10,
    3
);
```

### Option C — Both filters

For complete control, declare the raw type as supported AND map it to itself in the type map. This stops the schema from defaulting to the raw type fallback path and makes your transformer the only place that decides the rendered shape.

```php
add_filter(
    'woocommerce_react_settings_supported_types',
    static function ( array $types ): array {
        $types[] = 'currency';
        return $types;
    }
);

add_filter(
    'woocommerce_react_settings_type_map',
    static function ( array $map ): array {
        $map['currency'] = 'currency';
        return $map;
    }
);
```

Use Option A unless you have a reason not to.

## The JS side

The runtime registry on `window.wcReactSettings` is the extension point for custom Edit components.

```js
window.wcReactSettings.registerFieldTypeTransformer(
    'currency',
    ( setting, baseField ) => ( {
        ...baseField,
        type: 'text',
        Edit: CurrencyEdit,
    } )
);
```

The transformer receives:

- `setting` — the raw WooCommerce settings array entry: `{ id, title, type, default, desc, options? ... }`. This is exactly what your `get_settings_for_*_section()` returns for that field.
- `baseField` — a partially-normalized DataForm field shape: `{ id, label, type, description? }`. The `id` and `label` are derived from the raw setting's `id` and `title`/`name`; the `type` is the result of running the type map.

The transformer must return a [DataForm field](https://developer.wordpress.org/block-editor/reference-guides/data/data-core/) shape. At minimum: `{ ...baseField, type }`. Optionally: `Edit`, `getValue`, `setValue`, `isValid`, `elements`.

A custom `Edit` component receives:

```ts
type EditProps< Value > = {
    data: Record< string, unknown >;
    field: { id: string; label: string };
    onChange: ( next: Record< string, unknown > ) => void;
    hideLabelFromVision?: boolean;
};
```

`data` is the form-wide values object; the current field's value is `data[ field.id ]`. `onChange` is called with a partial values object — the renderer merges it into `data`.

## End-to-end walkthrough — a `currency` field

Let's wire a `currency` field type that renders as a text input with a `$` prefix.

### 1. Register the field on the PHP side

In your settings tab subclass:

```php
public function get_settings_for_default_section() {
    return array(
        array(
            'title' => __( 'Pricing', 'my-plugin' ),
            'type'  => 'title',
            'id'    => 'my_plugin_pricing',
        ),
        array(
            'title'   => __( 'Default price', 'my-plugin' ),
            'id'      => 'my_plugin_default_price',
            'type'    => 'currency',
            'default' => '0.00',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'my_plugin_pricing',
        ),
    );
}
```

Map the raw type to `text` so the schema treats it as renderable; the JS transformer will swap in the custom Edit:

```php
add_filter(
    'woocommerce_react_settings_type_map',
    static function ( array $map ): array {
        $map['currency'] = 'text';
        return $map;
    }
);
```

### 2. Enqueue and register the transformer

Ship a small JS file that reads from `window.wcReactSettings`:

```js
( function () {
    if ( ! window.wcReactSettings || ! window.wcReactSettings.registerFieldTypeTransformer ) {
        return;
    }

    var el = window.wp.element.createElement;

    function CurrencyEdit( props ) {
        var value = props.data[ props.field.id ] || '';
        return el( 'label', { className: 'my-plugin-currency' },
            el( 'span', null, '$' ),
            el( 'input', {
                type: 'text',
                inputMode: 'decimal',
                value: value,
                onChange: function ( event ) {
                    var next = {};
                    next[ props.field.id ] = event.target.value;
                    props.onChange( next );
                },
            } )
        );
    }

    window.wcReactSettings.registerFieldTypeTransformer(
        'currency',
        function ( setting, baseField ) {
            return Object.assign( {}, baseField, {
                type: 'text',
                Edit: CurrencyEdit,
            } );
        }
    );
} )();
```

Register it on the settings page only:

```php
add_action(
    'admin_enqueue_scripts',
    static function ( string $hook ): void {
        if ( 'woocommerce_page_wc-settings' !== $hook ) {
            return;
        }
        wp_enqueue_script(
            'my-plugin-currency-field',
            plugins_url( 'assets/currency-field.js', __FILE__ ),
            array( 'wp-element' ),
            '1.0.0',
            true
        );
    }
);
```

The `wp-element` dependency guarantees `window.wp.element` is available before your script runs. If you ship a real React/JSX bundle via `@wordpress/scripts`, depend on whatever your build emits — but for a one-file inline transformer this is enough.

### 3. Verify

1. Activate the plugin and enable the `modern-settings` flag.
2. Visit the settings tab. The currency field should render with a `$` prefix and accept input.
3. Save the form. The value should round-trip through the legacy save handler.
4. Disable the `modern-settings` flag. The same field should render via the legacy renderer with no change to your code.

## Testing your custom field type

There are two layers worth testing.

**Unit-test the transformer in isolation.** It is a pure function from `( setting, baseField ) → fieldShape`. You can call it directly in a Jest test and assert the returned shape, without rendering React.

```js
import { transformer } from './currency-field';

test( 'returns a text-typed field with a custom Edit', () => {
    const setting = { id: 'price', title: 'Price', type: 'currency' };
    const baseField = { id: 'price', label: 'Price', type: 'text' };

    const field = transformer( setting, baseField );

    expect( field.type ).toBe( 'text' );
    expect( field.Edit ).toBeDefined();
} );
```

**Smoke-test the rendered Edit.** Mount the Edit component with a controlled `data`/`onChange` pair and assert the input renders, accepts a value, and propagates a change. The standard `@testing-library/react` patterns apply.

For end-to-end, the [example plugin](./modernised-settings-sdk.md#example-plugin) is a good template — clone its directory layout and swap in your custom field type.

## Limits

- **No automatic label.** The renderer does not wrap your custom `Edit` in a `<label>`. If your input does not have its own `<label htmlFor="...">` or an `aria-label`, wrap it in `<BaseControl>` from `@wordpress/components` for accessibility.
- **No deregister API.** Transformers persist for the lifetime of the page. If you re-register the same type, the later registration wins.
- **Global namespace.** The registry is keyed by raw type string. If two plugins register the same type, the one whose script runs last wins. Pick a type name with a plugin-specific prefix (e.g. `my_plugin_currency`, not `currency`).
- **No type guarantees today.** TypeScript types for the registry will ship with the upcoming `@woocommerce/modern-settings-sdk` package. Until then, treat the field shape as documented above and validate in tests.
