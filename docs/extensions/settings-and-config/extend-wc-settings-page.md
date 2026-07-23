---
post_title: How to add a settings page
sidebar_label: Settings pages
sidebar_position: 2
---

# How to add a settings page

Use `WC_Settings_Page` when your extension needs a full tab under **WooCommerce > Settings**. A settings page class registers the tab, renders one or more sections, defines fields with WooCommerce's settings array format, and lets WooCommerce handle saving through the existing settings form.

If your extension only needs a few settings that clearly belong under an existing WooCommerce settings tab, add a section to that tab instead of creating a new top-level tab. See [How to add a section to a settings tab](./adding-a-section-to-a-settings-tab.md). If your settings belong to a payment gateway, shipping method, or integration, also check whether `WC_Payment_Gateway`, `WC_Shipping_Method`, `WC_Integration`, or `WC_Settings_API` is the better fit.

## How settings pages are loaded

WooCommerce loads settings page objects from `WC_Admin_Settings::get_settings_pages()`. Core pages are loaded first, then extensions can add their own page objects through the `woocommerce_get_settings_pages` filter.

A `WC_Settings_Page` subclass must set its `$id` and `$label` before calling `parent::__construct()`. The parent constructor uses the page ID to register these hooks:

- `woocommerce_settings_tabs_array` adds the settings tab.
- `woocommerce_sections_{$page_id}` outputs section navigation.
- `woocommerce_settings_{$page_id}` outputs the fields.
- `woocommerce_settings_save_{$page_id}` saves the fields.

## Create a settings page class

Load your class only after `WC_Settings_Page` is available. WooCommerce includes the base class before it applies `woocommerce_get_settings_pages`, so a common pattern is to require your settings page class from that filter callback.

The example below defines a full **My plugin** tab with two sections. Place the class in a file such as `includes/class-my-plugin-settings-page.php`, and load that file from the registration callback shown after the class.

```php
<?php
defined( 'ABSPATH' ) || exit;

final class My_Plugin_Settings_Page extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'my_plugin';
		$this->label = __( 'My plugin', 'my-plugin' );

		parent::__construct();
	}

	protected function get_own_sections() {
		return array(
			''         => __( 'General', 'my-plugin' ),
			'advanced' => __( 'Advanced', 'my-plugin' ),
		);
	}

	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'My plugin settings', 'my-plugin' ),
				'type'  => 'title',
				'desc'  => __( 'Configure the default behavior for My plugin.', 'my-plugin' ),
				'id'    => 'my_plugin_options',
			),
			array(
				'title'    => __( 'Enable feature', 'my-plugin' ),
				'desc'     => __( 'Turn on the main plugin feature.', 'my-plugin' ),
				'id'       => 'my_plugin_enabled',
				'type'     => 'checkbox',
				'default'  => 'no',
			),
			array(
				'title'    => __( 'Display title', 'my-plugin' ),
				'id'       => 'my_plugin_display_title',
				'type'     => 'text',
				'default'  => __( 'Featured products', 'my-plugin' ),
				'desc'     => __( 'Shown to customers when the feature appears on the storefront.', 'my-plugin' ),
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'my_plugin_options',
			),
		);
	}

	protected function get_settings_for_advanced_section() {
		return array(
			array(
				'title' => __( 'Advanced settings', 'my-plugin' ),
				'type'  => 'title',
				'id'    => 'my_plugin_advanced_options',
			),
			array(
				'title'    => __( 'Mode', 'my-plugin' ),
				'id'       => 'my_plugin_mode',
				'type'     => 'select',
				'default'  => 'automatic',
				'options'  => array(
					'automatic' => __( 'Automatic', 'my-plugin' ),
					'manual'    => __( 'Manual', 'my-plugin' ),
				),
				'desc_tip' => __( 'Choose how My plugin should choose products.', 'my-plugin' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'my_plugin_advanced_options',
			),
		);
	}
}
```

Register the settings page from your main plugin file:

```php
add_filter(
	'woocommerce_get_settings_pages',
	function ( array $settings_pages ): array {
		require_once __DIR__ . '/includes/class-my-plugin-settings-page.php';

		$settings_pages[] = new My_Plugin_Settings_Page();
		return $settings_pages;
	}
);
```

The page is available at:

```text
wp-admin/admin.php?page=wc-settings&tab=my_plugin
```

The `advanced` section is available at:

```text
wp-admin/admin.php?page=wc-settings&tab=my_plugin&section=advanced
```

## Define sections

Override `get_own_sections()` when your page has more than one section. Return an associative array where each key is a section ID and each value is the translated section label.

The default section uses an empty string as its key:

```php
protected function get_own_sections() {
	return array(
		''         => __( 'General', 'my-plugin' ),
		'advanced' => __( 'Advanced', 'my-plugin' ),
	);
}
```

If the page has only one section, you can omit `get_own_sections()` and WooCommerce will use a single **General** section. Section IDs appear in URLs, so keep them stable and use lowercase values. If you rely on the `get_settings_for_{$section_id}_section()` method-name convention, use section IDs that are valid in PHP method names, such as `advanced` or `my_plugin_section`.

## Define settings for each section

For new settings pages, define fields with section-specific methods:

- `get_settings_for_default_section()` returns fields for the default section.
- `get_settings_for_{$section_id}_section()` returns fields for a named section, such as `get_settings_for_advanced_section()`. This convention requires the section ID to work in a PHP method name.
- `get_settings_for_section_core( $section_id )` can be overridden when the page needs one generic method for many dynamic sections.

Do not override `get_settings()` for new code. WooCommerce still calls it internally for backward compatibility with older extensions, but it is deprecated. New code should use the section-specific methods above.

Each method returns a WooCommerce settings array. A section usually starts with a `title` field and ends with a matching `sectionend` field.

```php
protected function get_settings_for_default_section() {
	return array(
		array(
			'title' => __( 'My plugin settings', 'my-plugin' ),
			'type'  => 'title',
			'id'    => 'my_plugin_options',
		),
		array(
			'title'   => __( 'Items per page', 'my-plugin' ),
			'id'      => 'my_plugin_items_per_page',
			'type'    => 'number',
			'default' => '12',
			'custom_attributes' => array(
				'min'  => '1',
				'step' => '1',
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'my_plugin_options',
		),
	);
}
```

## Field array reference

WooCommerce renders and saves fields based on each field array.

| Key | Purpose |
| --- | ------- |
| `id` | Option name and HTML field ID. Required for fields that save an option. |
| `type` | Field type, such as `text`, `checkbox`, `select`, `textarea`, or `title`. |
| `title` | Label shown in the left column. Older examples may use `name`; prefer `title` for new code. |
| `desc` | Description text shown near the field. |
| `desc_tip` | `true` to use `desc` as a tooltip, or a string to use as tooltip text. |
| `default` | Default value used when the option is not set. |
| `options` | Key/value choices for `select`, `multiselect`, and `radio` fields. |
| `class` | CSS class for the input. |
| `css` | Inline CSS for the input. Use sparingly. |
| `placeholder` | Placeholder text for supported input types. |
| `custom_attributes` | Extra HTML attributes such as `min`, `max`, `step`, or `required`. |
| `autoload` | Whether the saved option should autoload. Defaults to `true`. Use `false` for rarely used options. |
| `field_name` | Input name when it must differ from `id`, including array-style names. |
| `is_option` | Set to `false` for rendered fields that should not be saved as options. |

Common field types include:

- `title`
- `sectionend`
- `text`
- `password`
- `number`
- `email`
- `url`
- `textarea`
- `select`
- `multiselect`
- `radio`
- `checkbox`
- `single_select_page`
- `single_select_page_with_search`
- `single_select_country`
- `multi_select_countries`
- `relative_date_selector`

Custom field types can be rendered with the `woocommerce_admin_field_{$type}` action.

## Saving settings

When the merchant saves a settings page, WooCommerce verifies the `manage_woocommerce` capability and the settings nonce, then fires the page-specific save hooks. For a `WC_Settings_Page` subclass, the registered `save()` method saves the current section's fields with `WC_Admin_Settings::save_fields()`.

The save routine:

1. Reads submitted values from `$_POST`.
2. Sanitizes values according to field type.
3. Applies `woocommerce_admin_settings_sanitize_option`.
4. Applies `woocommerce_admin_settings_sanitize_option_{$option_name}`.
5. Updates the WordPress option named by `field_name` or `id`.

Checkboxes are saved as `yes` or `no`. Select fields are constrained to the keys in `options`. Password fields are trimmed but otherwise preserved so special characters are not corrupted.

Use `get_option( 'my_plugin_enabled', 'no' )` or `WC_Admin_Settings::get_option( 'my_plugin_enabled', 'no' )` to read saved values.

## Add custom sanitization

Use the option-specific sanitize filter when a field needs validation beyond the built-in field type handling.

```php
add_filter(
	'woocommerce_admin_settings_sanitize_option_my_plugin_items_per_page',
	function ( $value, array $option, $raw_value ) {
		return (string) max( 1, absint( $raw_value ) );
	},
	10,
	3
);
```

Return `null` to skip updating the option.

## Run extra work after saving

Use the section-specific update action when saving a section needs side effects, such as clearing caches or scheduling work.

```php
add_action(
	'woocommerce_update_options_my_plugin_advanced',
	function (): void {
		delete_transient( 'my_plugin_expensive_data' );
	}
);
```

The default section does not fire a section-specific `woocommerce_update_options_{$page_id}_{$section_id}` action because its section ID is empty. If you need custom behavior for every save on the tab, use `woocommerce_update_options_{$page_id}`:

```php
add_action(
	'woocommerce_update_options_my_plugin',
	function (): void {
		delete_transient( 'my_plugin_expensive_data' );
	}
);
```

Override `save()` only when the page must change the order of operations or save non-option data. If you override it, call `save_settings_for_current_section()` when the normal fields should still be persisted.

## Add a section to an existing settings page

Many extensions should not create a new settings tab. If the settings belong under an existing tab, register a section under that tab instead.

For WooCommerce versions that support the settings section registry, create a `SettingsSection` and register it on `woocommerce_settings_sections_registration`. This keeps section metadata, fields, Settings UI scripts, and save behavior together.

```php
<?php
use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;

final class My_Plugin_Products_Settings_Section extends SettingsSection {
	public function get_parent_page_id(): string {
		return 'products';
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
				'id'    => 'my_plugin_products_options',
			),
			array(
				'title'   => __( 'Enable product display', 'my-plugin' ),
				'desc'    => __( 'Show My plugin content on product pages.', 'my-plugin' ),
				'id'      => 'my_plugin_product_display_enabled',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'my_plugin_products_options',
			),
		);
	}
}

add_action(
	'woocommerce_settings_sections_registration',
	function ( SettingsSectionRegistry $registry ): void {
		$registry->register( new My_Plugin_Products_Settings_Section() );
	}
);
```

For older WooCommerce versions, use the filter-based approach documented in [How to add a section to a settings tab](./adding-a-section-to-a-settings-tab.md).

## Opt in to the React settings UI

`WC_Settings_Page` still owns registration, permissions, schema, and persistence when a page opts in to the React settings UI. To render a page with the React settings UI when the feature flag is enabled, return a settings UI adapter from `get_settings_ui_page()`.

```php
use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

public function get_settings_ui_page(): ?SettingsUIPageInterface {
	return new LegacySettingsPageAdapter( $this );
}
```

This is optional. If the feature flag is disabled, or the page does not return an adapter, WooCommerce renders the classic PHP settings table. See [Settings UI](./settings-ui.md) for custom React components, script handles, save adapters, and migration details.

## Further reading

- [WooCommerce Settings API](./settings-api.md)
- [How to add a section to a settings tab](./adding-a-section-to-a-settings-tab.md)
- [Settings UI](./settings-ui.md)
- [WC_Settings_Page code reference](https://woocommerce.github.io/code-reference/classes/WC-Settings-Page.html)
- [WC_Admin_Settings code reference](https://woocommerce.github.io/code-reference/classes/WC-Admin-Settings.html)
