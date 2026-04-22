<?php
/**
 * ReactSettingsPageInterface implementation for the Modern Example tab.
 *
 * @package ModernSettingsExample
 */

declare( strict_types=1 );

namespace Modern_Settings_Example;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Modern Example React-settings contract.
 *
 * Paired with `Modern_Settings_Example_Tab` (the legacy `WC_Settings_Page`
 * subclass) to demonstrate the two-class pattern for modernised settings.
 *
 * This example ships only natively-supported field types, so it does not
 * need to contribute extra supported types or type aliases. It also ships
 * no fields that need server-side option synthesis.
 */
final class Modern_Settings_Example_React_Page implements ReactSettingsPageInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_type_map( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_extra_supported_types( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_field_options( string $field_id, array $field, string $section ): ?array {
		return null;
	}
}
