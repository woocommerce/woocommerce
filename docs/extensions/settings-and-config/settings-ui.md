---
post_title: Settings UI
sidebar_label: Settings UI
sidebar_position: 7
---

# Settings UI

The settings UI is an experimental, opt-in path for rendering WooCommerce settings pages with React while keeping the existing `WC_Settings_Page` registration and save flow.

It is designed for extension authors who want to migrate incrementally. PHP still owns page registration, settings schema, permissions, script dependencies, and persistence. React owns field rendering and client-side interaction.

## Status

> **The settings UI is experimental.** Until it is marked as stable, the PHP API under `Automattic\WooCommerce\Admin\Settings`, the schema format, and the `@woocommerce/settings-ui` package can change in backwards-incompatible ways in any release. Expect to update integrations between WooCommerce versions.

-   The settings UI is behind the `settings-ui` feature flag.
-   With the flag disabled, settings pages keep the legacy PHP renderer.
-   With the flag enabled, a settings page still has to opt in explicitly.
-   Saves use the existing WooCommerce settings form POST flow by default.
-   The public PHP API is available under `Automattic\WooCommerce\Admin\Settings`.

## Build a settings UI integration

A complete integration has the same pieces whether it is a full settings tab or a section inside an existing tab:

1. Choose the location: a new `WC_Settings_Page` tab, or a registered section under an existing tab.
2. Define fields in PHP using the WooCommerce settings array. This remains the source of truth for labels, descriptions, defaults, option ids, and fallback rendering.
3. Add Settings UI metadata to fields that need custom React rendering, such as a stable `component` name.
4. Return any script handles that register those custom components before the settings UI mounts.
5. Use the default `form_post` save adapter unless the field is display-only or manages persistence separately.

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

## Register a section under an existing settings tab

Extensions can register a complete settings section under an existing WooCommerce settings tab. The section object defines where the section lives, how it is labelled, which fields it renders, which scripts power custom React components, and how fields are saved.

This is useful for payment providers or integrations that should live inside a Core-owned tab such as **WooCommerce > Settings > Payments**.

```php
<?php
use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;

final class My_Plugin_Settings_Section extends SettingsSection {
	public function get_parent_page_id(): string {
		return 'checkout';
	}

	public function get_id(): string {
		return 'my_plugin';
	}

	public function get_label(): string {
		return __( 'My plugin', 'my-plugin' );
	}

	public function get_settings( WC_Settings_Page $parent_page ): array {
		return array(
			array(
				'title' => __( 'My plugin', 'my-plugin' ),
				'type'  => 'title',
				'id'    => 'my_plugin_options',
			),
			array(
				'title'     => __( 'Payment methods', 'my-plugin' ),
				'id'        => 'my_plugin_payment_methods',
				'type'      => 'multiselect',
				'component' => 'my-plugin/payment-method-picker',
				'options'   => array(
					'card' => __( 'Card', 'my-plugin' ),
					'bnpl' => __( 'Buy now, pay later', 'my-plugin' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'my_plugin_options',
			),
		);
	}

	public function get_script_handles( WC_Settings_Page $parent_page ): array {
		return array( 'my-plugin-settings-ui' );
	}

	// The inherited save adapter is `form_post` by default.
}

add_action(
	'woocommerce_settings_sections_registration',
	function ( SettingsSectionRegistry $registry ): void {
		$registry->register( new My_Plugin_Settings_Section() );
	}
);
```

WooCommerce creates the settings UI adapter for registered sections internally. When the settings UI feature flag is disabled, WooCommerce falls back to the legacy settings returned by `get_settings()`. Saves continue through the existing WooCommerce settings form flow and section-specific hooks such as `woocommerce_update_options_checkout_my_plugin`.

Use a section id that does not conflict with an existing section on the same settings tab. For the `checkout` tab, ids that match existing payment gateway sections are reserved.

### Provide a native Settings UI page for a registered section

Sections with custom navigation, save handlers, or native Settings UI schemas can provide their own Settings UI page instead of using the legacy settings adapter.

```php
<?php
use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

final class My_Plugin_Settings_Section extends SettingsSection {
	// Other settings section methods omitted for brevity.

	public function get_settings_ui_page( WC_Settings_Page $parent_page ): ?SettingsUIPageInterface {
		return new My_Plugin_Settings_UI_Page( $parent_page );
	}
}
```

When `get_settings_ui_page()` returns a `SettingsUIPageInterface`, WooCommerce uses it directly for the registered section. Returning `null` keeps the default behavior: WooCommerce converts the section's legacy `get_settings()` array into a Settings UI schema.

### Section navigation on native pages

The Settings UI shell renders sibling-section navigation from the `shell.sectionNavigation` schema key. How it applies depends on where the page is registered:

-   **Top-level pages** never render shell section navigation. The classic section links render with the settings header instead, and any schema-provided value is cleared.
-   **Drill-down pages** default to no section navigation, since the header breadcrumbs replace it. Setting a custom array renders it as tabs under the header; each entry needs `id`, `label`, `href`, and `active` keys.

```php
// Custom navigation entry shape.
$schema['shell']['sectionNavigation'] = array(
	array(
		'id'     => 'my_section',
		'label'  => __( 'My section', 'my-plugin' ),
		'href'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=my_section' ),
		'active' => true,
	),
);
```

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

Omit `scope.section` for a page-wide registration. Use `section: ''` for the default section only, or pass a section id such as `section: 'payments'` for one named section.

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

## Page header

The shell header (the page title, badges, breadcrumbs, and the top save button) is reserved for drill-down pages. WooCommerce decides this from the page registration, so a page cannot change it through its schema:

- Pages registered at the top level of settings, whether a `WC_Settings_Page` tab or a registered section, render without the header. The top-level settings tabs stay visible, pages with sections keep the classic section links, and the save button appears at the bottom of the page.
- Sections of the Payments tab render as drill-down pages. The header replaces the top-level settings tabs, and its breadcrumbs default to a link back to the Payments tab when the schema does not provide any. The save button renders in the header.

A settings UI page that supplies its own schema (via `SettingsUIPageInterface::get_schema()`) can set header content through the `shell` key for drill-down pages. Alongside `title` and `breadcrumbs`, the header supports a `subtitle` and `badges`:

```php
$schema['shell']['subtitle'] = __( 'Manage your store payment settings.', 'my-plugin' );
$schema['shell']['badges']   = array(
	array(
		'label'  => __( 'Active', 'my-plugin' ),
		'intent' => 'success', // default | info | success | warning | error
	),
);
```

`subtitle` renders under the page title. Each badge renders as a pill next to the title; `intent` selects its color. Both are plain text and are escaped on render.

`intent` is decorative styling only — it conveys meaning through color. The badge `label` must be self-descriptive so screen-reader and color-blind users get the same information (e.g. prefer `"Active"` or `"Beta"` over generic text). Unknown `intent` values fall back to `default`.

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
