<?php
/**
 * ReactSettingsPageInterface.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Contract a WC_Settings_Page supplies to participate in the modernised settings SDK.
 *
 * A page participates by returning an instance of this interface from
 * `WC_Settings_Page::get_react_settings_page()`. When the method returns `null`
 * (the default on the base class), the page is treated as opted out of modern
 * rendering regardless of the `modern-settings` feature flag.
 *
 * @since 10.8.0
 */
interface ReactSettingsPageInterface {

	/**
	 * Additional field-type aliases this page contributes for the given section.
	 *
	 * Merged into ReactSettingsSchema's global type map. Map alias => canonical type,
	 * e.g. `[ 'relative_date_selector' => 'text' ]` renders an unsupported type as its
	 * nearest supported equivalent. Pages that want the same aliases across all
	 * sections ignore the `$section` argument; section-specific pages branch on it.
	 *
	 * @since 10.8.0
	 *
	 * @param string $section Section id. Empty string means the default section.
	 *
	 * @return array<string, string>
	 */
	public function get_extra_type_map( string $section ): array;

	/**
	 * Additional field types this page knows how to render natively in the given section.
	 *
	 * Merged into ReactSettingsSchema's global supported-types list. Use this when the
	 * page ships a custom field transformer on the JS side and needs the PHP gate to
	 * allow the type through. Pages that want the same supported types across all
	 * sections ignore the `$section` argument; section-specific pages branch on it.
	 *
	 * @since 10.8.0
	 *
	 * @param string $section Section id. Empty string means the default section.
	 *
	 * @return array<int, string>
	 */
	public function get_extra_supported_types( string $section ): array;

	/**
	 * Server-side option synthesis for a field that does not ship its `options` inline.
	 *
	 * Called once per field during transform. Return `null` when the page has no opinion
	 * about this field — ReactSettingsSchema then keeps the inline options (from the
	 * field definition) or the built-in page-list fallback (for
	 * `single_select_page_with_search`). Return a non-null array to override.
	 *
	 * @since 10.8.0
	 *
	 * @param string $field_id Field id.
	 * @param array  $field    Raw settings definition.
	 * @param string $section  Section id. Empty string means the default section.
	 *
	 * @return array<int, array{label: string, value: string}>|null
	 */
	public function get_field_options( string $field_id, array $field, string $section ): ?array;
}
