---
post_title: Migrate to canonical Settings UI values
sidebar_label: Settings UI API migration
sidebar_position: 7
---

# Migrate to canonical Settings UI values

> **Experimental.** The Settings UI is behind the default-off `settings-ui` feature flag. Its PHP and JavaScript APIs remain subject to change while the feature matures.

Settings UI now uses canonical typed values from PHP through React and DataForm. Legacy form values are restored only when WooCommerce builds the hidden inputs used by the `form_post` save adapter.

No PHP interface methods or JavaScript registration functions were removed. Consumers that inspect settings values or provide a native schema may need to update their value handling.

## Value changes

| Field type       | Canonical value                           | Cleared value          |
| ---------------- | ----------------------------------------- | ---------------------- |
| `checkbox`       | `boolean`                                 | `false`                |
| `integer`        | `number` containing an integer            | `null`                 |
| `number`         | `number`                                  | `null`                 |
| `array`          | `string[]`                                | `[]`                   |
| `select`         | `string`                                  | `''`                   |
| `radio`          | `string`                                  | `''`                   |
| `datetime-local` | ISO datetime string with a timezone       | `null`                 |
| Other scalar     | Existing canonical scalar representation  | Depends on the control |

The legacy adapter performs these conversions while building the schema. For example:

```php
array(
    'id'                => 'my_plugin_retry_count',
    'type'              => 'number',
    'value'             => '02',
    'custom_attributes' => array(
        'min'  => 0,
        'max'  => 10,
        'step' => 1,
    ),
)
```

Produces a canonical field equivalent to:

```php
array(
    'id'         => 'my_plugin_retry_count',
    'type'       => 'integer',
    'value'      => 2,
    'validation' => array(
        'min' => 0,
        'max' => 10,
    ),
    'save'       => array(
        'adapter'      => 'form_post',
        'name'         => 'my_plugin_retry_count',
        'initialValue' => '02',
    ),
)
```

React and DataForm receive `2`. If the field remains unchanged, the form POST still submits the original value, `02`. After a change, WooCommerce submits the new canonical number as a string.

## Datetime values

A legacy `datetime-local` value has no timezone. The legacy adapter interprets it in the configured WordPress timezone and emits an ISO value for React.

For a site using `America/New_York`:

```text
Stored value:   2026-07-17T13:30:45
React value:    2026-07-17T17:30:45+00:00
```

DataForm continues using ISO values while the page is open. The `form_post` encoder converts a changed value back to the current WordPress timezone. An unchanged value uses `save.initialValue`, preserving its original local representation and precision.

Custom save handlers receive the ISO value. They do not receive the local form POST representation.

## Cleared values

DataForm controls can emit `undefined` when a number or datetime is cleared. Settings UI stores this as `null` so the schema and page state remain serializable.

Code that previously checked for an empty string should also handle the new canonical value:

```ts
const isEmpty = value === null;
```

The `form_post` encoder submits `null` as an empty string for compatibility with `WC_Admin_Settings::save_fields()`.

## Validation metadata

Numeric `min` and `max` attributes now become explicit Settings UI validation metadata:

```php
'validation' => array(
    'min' => 0,
    'max' => 10,
),
```

DataForm receives these values through its field validity rules. The frontend adapter no longer interprets `customAttributes`.

`customAttributes` remains available as metadata for custom components, but built-in controls do not apply arbitrary HTML attributes. Use a registered component and validator when a field needs behavior that the canonical schema cannot express.

## Custom components

Registered controls still receive `SettingsEditControlProps`. The values in `data`, `field.getValue()`, and `field.setValue()` now follow the canonical contract.

Before:

```ts
const value = field.getValue( { item: data } );
// The control value could differ from data[field.id], and setValue restored
// the legacy saved representation.
```

After:

```ts
const value = field.getValue( { item: data } );
// value and data[field.id] use the same canonical representation.

onChange(
    field.setValue( {
        item: data,
        value: 3,
    } )
);
```

Do not convert canonical values back to `yes`, numeric strings, or local datetime strings inside a component. The active save adapter owns persistence conversion.

## Validators, visibility, and regions

The following extension callbacks now receive canonical `values` and `initialValues`:

- Component validators
- Field visibility predicates
- Group visibility predicates
- Region components
- Custom save handlers

Update strict comparisons accordingly:

```ts
// Before.
const isEnabled = values.my_plugin_enabled === 'yes';

// After.
const isEnabled = values.my_plugin_enabled === true;
```

For numbers:

```ts
// Before.
const limit = Number( values.my_plugin_limit );

// After.
const limit = values.my_plugin_limit;
```

Custom validators continue returning an error message or `null`. They should validate the canonical value rather than its form POST representation.

## Custom save handlers

Custom save handlers receive canonical values without form POST conversion:

```ts
registerSettingsExtension( {
    scope: { page: 'my_plugin' },
    saveHandlers: {
        'my-plugin/save': async ( { values } ) => {
            await saveSettings( {
                enabled: values.my_plugin_enabled, // boolean
                limit: values.my_plugin_limit, // number or null
                startsAt: values.my_plugin_starts_at, // ISO string or null
            } );
        },
    },
} );
```

Convert these values only if the handler's own storage API requires a different format.

## Native schema implementations

Implementations of `SettingsUIPageInterface::get_schema()` must return canonical values directly. Native schemas do not pass through the legacy field normalizer.

A native numeric field should use a number or `null`:

```php
array(
    'id'    => 'my_plugin_limit',
    'label' => __( 'Limit', 'my-plugin' ),
    'type'  => 'number',
    'value' => 5,
)
```

A native datetime field should use an ISO value with an explicit timezone:

```php
array(
    'id'    => 'my_plugin_starts_at',
    'label' => __( 'Starts at', 'my-plugin' ),
    'type'  => 'datetime-local',
    'value' => '2026-07-17T17:30:45+00:00',
)
```

For `form_post` fields, provide `save.initialValue` when the original submitted representation should remain unchanged until the user edits the field. If it is omitted, WooCommerce encodes the canonical value.

## Integer type renderers

A legacy number field with `step="1"` and an integer step base now uses the canonical `integer` type. A renderer registered only under `typeRenderers.number` will not receive that field.

Register both types when they use the same control:

```ts
registerSettingsExtension( {
    scope: { page: 'my_plugin' },
    typeRenderers: {
        number: NumericControl,
        integer: NumericControl,
    },
} );
```

## Classic fallback requirements

WooCommerce validates a canonical schema before emitting the React mount. It also checks that the Settings UI package and page-provided extension scripts are registered.

If schema generation, schema validation, or script registration fails, WooCommerce logs developer feedback and renders classic settings in the same response.

An opted-in page must therefore retain a working legacy settings definition through `get_settings()`. Register scripts returned by `get_script_handles()` during or before `admin_enqueue_scripts`.

A React error after mounting does not switch renderers. Settings UI logs the error and offers to reload the page.

## Migration checklist

1. Update native schemas to use canonical field values.
2. Replace string comparisons for checkboxes and numbers.
3. Treat cleared number and datetime values as `null`.
4. Expect ISO values in datetime components and custom save handlers.
5. Register type renderers for `integer` where needed.
6. Move persistence conversion out of components and into the relevant save handler.
7. Keep a working classic `get_settings()` definition.
8. Register every script handle before the settings page renders.
9. Test existing stored values before opting a page into Settings UI.
