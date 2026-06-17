---
post_title: Settings UI
sidebar_label: Settings UI
sidebar_position: 5
---

# Settings UI

The settings UI is an opt-in path for rendering WooCommerce settings pages with React while keeping the existing `WC_Settings_Page` registration and save flow.

It is designed for extension authors who want to migrate incrementally. PHP still owns page registration, settings schema, permissions, script dependencies, and persistence. React owns field rendering and client-side interaction.

## Status

-   The settings UI is behind the `settings-ui` feature flag.
-   With the flag disabled, settings pages keep the legacy PHP renderer.
-   With the flag enabled, a settings page still has to opt in explicitly.
-   Saves use the existing WooCommerce settings form POST flow by default.
-   The public PHP API is available under `Automattic\WooCommerce\Admin\Settings`.

## Enable the feature flag

For local testing, enable the feature with a small mu-plugin:

```php
<?php
add_filter(
	'woocommerce_admin_features',
	static function ( array $features ): array {
		$features[] = 'settings-ui';
		return array_values( array_unique( $features ) );
	}
);
```

## Opt in a settings page

A `WC_Settings_Page` subclass opts in by returning a settings UI adapter from `get_settings_ui_page()`.

For pages that only need native fields, use `LegacySettingsPageAdapter`:

```php
<?php
use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

class My_Plugin_Settings_Page extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'my_plugin';
		$this->label = __( 'My plugin', 'my-plugin' );

		parent::__construct();
	}

	public function get_settings_ui_page(): ?SettingsUIPageInterface {
		return new LegacySettingsPageAdapter( $this );
	}
}
```

WooCommerce only uses the adapter when the `settings-ui` feature flag is enabled. Returning an adapter does not change the page while the feature flag is disabled.

## Native field migration

The legacy adapter converts the existing `get_settings()` array into a canonical schema for React. It supports common settings fields:

-   `text`
-   `password`
-   `email`
-   `url`
-   `tel`
-   `number`
-   `textarea`
-   `checkbox`
-   `select`
-   `radio`
-   `multiselect`
-   `multi_select_countries`
-   `single_select_country`
-   `single_select_page`
-   `info`

Fields before the first `title` marker are placed into a default group automatically.

The default save adapter is `form_post`, which serializes hidden inputs so `WC_Admin_Settings::save_fields()` continues to save the submitted values.

## Custom component migration

If a field needs a custom React UI, declare a component name in the PHP field schema:

```php
array(
	'id'        => 'my_plugin_payment_methods',
	'title'     => __( 'Payment methods', 'my-plugin' ),
	'type'      => 'multiselect',
	'component' => 'my-plugin/payment-method-picker',
	'options'   => array(
		'card' => __( 'Card', 'my-plugin' ),
		'bnpl' => __( 'Buy now, pay later', 'my-plugin' ),
	),
)
```

Then register that component from JavaScript:

```ts
import { registerSettingsExtension } from '@woocommerce/settings-ui';
import { PaymentMethodPicker } from './payment-method-picker';

registerSettingsExtension( {
	scope: {
		page: 'my_plugin',
	},
	components: {
		'my-plugin/payment-method-picker': PaymentMethodPicker,
	},
} );
```

See [Registering settings UI components](./registering-settings-ui-components.md) for the full component contract.

## Load extension scripts before mount

Custom component scripts must load before the settings app mounts. Return their registered WordPress script handles from the adapter:

```php
<?php
use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;

final class My_Plugin_Settings_UI_Page extends LegacySettingsPageAdapter {
	public function get_script_handles( string $section ): array {
		return array( 'my-plugin-settings-ui' );
	}
}
```

The settings embed script depends on the settings UI package and these handles only for the opted-in page. Other settings pages do not load it.

## Save adapters

The settings UI supports two save adapters:

| Adapter     | Behavior                                                             |
| ----------- | -------------------------------------------------------------------- |
| `form_post` | Serializes hidden inputs for the existing WooCommerce settings form. |
| `none`      | Does not submit a value. Use for display-only fields.                |

The legacy adapter uses `form_post` by default. A field can override its save behavior:

```php
array(
	'id'   => 'my_plugin_read_only_notice',
	'type' => 'info',
	'text' => __( 'This field is display only.', 'my-plugin' ),
	'save' => array(
		'adapter' => 'none',
	),
)
```

## Rich group descriptions and actions

Group title rows can include sanitized description markup and structured header actions. Use this for contextual links such as documentation or secondary actions that belong to the whole group, rather than creating a display-only custom field.

```php
array(
	'id'      => 'my_plugin_checkout',
	'type'    => 'title',
	'title'   => __( 'Checkout experience', 'my-plugin' ),
	'desc'    => sprintf(
		/* translators: %s: documentation link */
		__( 'Choose where customers can use express payment methods. %s', 'my-plugin' ),
		'<a href="' . esc_url( 'https://example.com/docs' ) . '">' . esc_html__( 'Learn more', 'my-plugin' ) . '</a>'
	),
	'actions' => array(
		array(
			'id'      => 'manage',
			'label'   => __( 'Manage locations', 'my-plugin' ),
			'href'    => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			'variant' => 'secondary',
		),
	),
)
```

Descriptions are sanitized with `wp_kses_post()`. Actions are structured data with `id`, `label`, `href`, optional `variant`, optional `target`, and optional `rel`.

## Reference migration in WooCommerce core

The Products settings page is the Core reference migration. With `settings-ui` enabled, the Products tab renders through the settings UI. With the flag disabled, it renders through the existing legacy settings UI.

Use this page to verify the native migration path before testing a plugin-specific page such as WooPayments.

## Testing an extension integration

1. Enable the `settings-ui` feature flag.
2. Return a settings UI adapter from your `WC_Settings_Page` subclass.
3. Start with native fields and confirm the page renders and saves.
4. Add `component` metadata only for fields that need custom UI.
5. Register scoped JavaScript components with `registerSettingsExtension()`.
6. Return custom script handles from `get_script_handles()` so they load before mount.
7. Disable the feature flag and confirm the legacy page still renders unchanged.

## Diagnostics

In development, the settings UI logs warnings for common integration issues:

-   The settings payload is missing.
-   The `wc-settings-ui` script is missing for a settings UI mount.
-   A field declares a component that is not registered.
-   A field type is unsupported.
-   A field declares an unknown save adapter.
