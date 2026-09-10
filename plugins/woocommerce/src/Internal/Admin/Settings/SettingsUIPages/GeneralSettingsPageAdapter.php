<?php
/**
 * General settings adapter for settings UI.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIPages;

use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts the WooCommerce General settings page for the settings UI renderer.
 *
 * The classic renderer shows and hides the country pickers from jQuery in
 * `client/legacy/js/admin/settings.js`, which only knows about the classic
 * table markup. This adapter restates those rules as schema visibility so the
 * settings UI hides the same fields.
 *
 * @since 11.2.0
 */
final class GeneralSettingsPageAdapter extends LegacySettingsPageAdapter {

	/**
	 * Apply the General settings visibility behavior.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return array
	 * @throws \InvalidArgumentException When a required General setting is unavailable.
	 */
	public function get_schema( string $section ): array {
		$schema = parent::get_schema( $section );
		$fields = $this->get_fields_by_id( $schema );

		$this->require_fields(
			$fields,
			array(
				'woocommerce_allowed_countries',
				'woocommerce_all_except_countries',
				'woocommerce_specific_allowed_countries',
				'woocommerce_ship_to_countries',
				'woocommerce_specific_ship_to_countries',
			)
		);

		$this->set_visibility( $schema, 'woocommerce_all_except_countries', 'woocommerce_allowed_countries', 'all_except' );
		$this->set_visibility( $schema, 'woocommerce_specific_allowed_countries', 'woocommerce_allowed_countries', 'specific' );
		$this->set_visibility( $schema, 'woocommerce_specific_ship_to_countries', 'woocommerce_ship_to_countries', 'specific' );

		// The preferred provider picker only exists when more than one provider is registered.
		if ( isset( $fields['woocommerce_address_autocomplete_provider'] ) ) {
			$this->require_fields( $fields, array( 'woocommerce_address_autocomplete_enabled' ) );
			$this->set_visibility( $schema, 'woocommerce_address_autocomplete_provider', 'woocommerce_address_autocomplete_enabled', true );
		}

		return $schema;
	}

	/**
	 * Get fields indexed by their stable identifiers.
	 *
	 * @param array $schema Settings UI schema.
	 * @return array<string, bool>
	 */
	private function get_fields_by_id( array $schema ): array {
		$fields = array();

		foreach ( $schema['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				$fields[ $field['id'] ] = true;
			}
		}

		return $fields;
	}

	/**
	 * Require fields used by the General settings behavior.
	 *
	 * A missing field means a filter reshaped the page, so the schema fails and
	 * the whole page falls back to the classic renderer rather than rendering
	 * country pickers that never hide.
	 *
	 * @param array<string, bool> $fields Fields indexed by identifier.
	 * @param string[]            $required_field_ids Required field identifiers.
	 * @throws \InvalidArgumentException When a required field is missing.
	 */
	private function require_fields( array $fields, array $required_field_ids ): void {
		foreach ( $required_field_ids as $field_id ) {
			if ( ! isset( $fields[ $field_id ] ) ) {
				throw new \InvalidArgumentException( 'General Settings UI requires all fields used by its visibility behavior.' );
			}
		}
	}

	/**
	 * Add visibility metadata to a field in the canonical schema.
	 *
	 * @param array       $schema Settings UI schema.
	 * @param string      $field_id Field identifier.
	 * @param string      $controller_id Controller field identifier.
	 * @param string|bool $value Controller value that makes the field visible.
	 * @return void
	 */
	private function set_visibility( array &$schema, string $field_id, string $controller_id, $value ): void {
		foreach ( $schema['groups'] as &$group ) {
			foreach ( $group['fields'] as &$field ) {
				if ( $field_id === $field['id'] ) {
					$field['visibility'] = array(
						'controller' => $controller_id,
						'value'      => $value,
					);
					unset( $field, $group );
					return;
				}
			}
			unset( $field );
		}
		unset( $group );
	}
}
