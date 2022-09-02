<?php
/**
 * Class WC_Settings_Unit_Test_Case file.
 *
 * @package WooCommerce\Tests\Settings
 */

/**
 * Base class for settings related unit tests.
 */
abstract class WC_Settings_Unit_Test_Case extends WC_Unit_Test_Case {

	/**
	 * Given a settings array return the one having the given identifier.
	 *
	 * @param array  $settings Settings array.
	 * @param string $id Identifier to find for.
	 *
	 * @return array|null The setting, or null if no setting exists in the supplied array with the supplied identifier.
	 */
	public function setting_by_id( $settings, $id ) {
		foreach ( $settings as $setting ) {
			if ( $id === $setting['id'] ) {
				return $setting;
			}
		}

		return null;
	}

	/**
	 * Given a settings array return an associative array of id => type.
	 * If more than one setting has the same id then the returned array is id => array of types.
	 *
	 * @param array $settings The settings to transform.
	 *
	 * @return array The transformed settings.
	 */
	public function get_ids_and_types( $settings ) {
		$settings_ids_and_types = array();
		foreach ( $settings as $setting ) {
			$id   = array_key_exists( 'id', $setting ) ? $setting['id'] : null;
			$type = $setting['type'];
			if ( ! array_key_exists( $id, $settings_ids_and_types ) ) {
				$settings_ids_and_types[ $id ] = $type;
			} elseif ( is_array( $settings_ids_and_types[ $id ] ) ) {
				$settings_ids_and_types[ $id ][] = $type;
			} else {
				$settings_ids_and_types[ $id ] = array( $settings_ids_and_types[ $id ], $type );
			}
		}

		return $settings_ids_and_types;
	}
}
