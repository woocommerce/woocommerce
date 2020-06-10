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
	 * @return |null The setting, or null if no setting exists in the supplied array with the supplied identifier.
	 */
	public function setting_by_id( $settings, $id ) {
		foreach ( $settings as $setting ) {
			if ( $id === $setting['id'] ) {
				return $setting;
			}
		}

		return null;
	}
}
