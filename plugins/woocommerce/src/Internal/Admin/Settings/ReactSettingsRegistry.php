<?php
/**
 * React Settings registry.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Schema\GeneralSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products\Schema\ProductSettingsSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for React settings screens.
 */
class ReactSettingsRegistry {
	/**
	 * Get registry entries keyed by ID.
	 *
	 * @return array
	 */
	public static function get_entries(): array {
		return array(
			'general'          => array(
				'tab'            => 'general',
				'section'        => '',
				'schema'         => GeneralSettingsSchema::class,
				'payloadPath'    => array( 'settings', 'general' ),
				'supportedTypes' => array( 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'toggle' ),
				'typeMap'        => array(
					'single_select_country'  => 'select',
					'multi_select_countries' => 'multiselect',
				),
			),
			'products.general' => array(
				'tab'            => 'products',
				'section'        => '',
				'schema'         => ProductSettingsSchema::class,
				'payloadPath'    => array( 'settings', 'products', 'general' ),
				'supportedTypes' => array( 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'toggle' ),
				'typeMap'        => array(
					'single_select_page'             => 'select',
					'single_select_page_with_search' => 'select',
				),
			),
			'products.inventory' => array(
				'tab'            => 'products',
				'section'        => 'inventory',
				'schema'         => ProductSettingsSchema::class,
				'payloadPath'    => array( 'settings', 'products', 'inventory' ),
				'supportedTypes' => array( 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'toggle' ),
				'typeMap'        => array(),
			),
		);
	}

	/**
	 * Get a registry entry for the provided tab and section.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @return array|null
	 */
	public static function get_entry_for_tab_section( string $tab, string $section ): ?array {
		foreach ( self::get_entries() as $entry ) {
			$entry_tab     = $entry['tab'] ?? '';
			$entry_section = $entry['section'] ?? '';

			if ( $entry_tab === $tab && $entry_section === $section ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Determine whether settings definitions are supported by the React UI.
	 *
	 * @param array $settings_definitions Settings definitions.
	 * @param array $type_map Map of WooCommerce types to normalized types.
	 * @param array $supported_types Supported normalized types.
	 * @return bool
	 */
	public static function supports_settings_definitions( array $settings_definitions, array $type_map, array $supported_types ): bool {
		foreach ( $settings_definitions as $setting ) {
			$type = $setting['type'] ?? '';

			if ( '' === $type ) {
				return false;
			}

			if ( in_array( $type, array( 'title', 'sectionend' ), true ) ) {
				continue;
			}

			$normalized_type = $type_map[ $type ] ?? $type;
			if ( ! in_array( $normalized_type, $supported_types, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build a consistent mount ID for a registry entry.
	 *
	 * @param array $entry Registry entry.
	 * @return string
	 */
	public static function get_mount_id( array $entry ): string {
		$tab     = $entry['tab'] ?? '';
		$section = $entry['section'] ?? '';
		$normalized_section = '' === $section ? 'default' : $section;

		return 'wc_settings_react_' . $tab . '_' . $normalized_section;
	}
}
