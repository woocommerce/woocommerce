<?php

/**
 * Class WC_Helper_Settings.
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_Helper_Settings {

	/**
	 * Hooks in some dummy data for testing the settings REST API.
	 * @since 2.7.0
	 */
	public static function register() {
		add_filter( 'woocommerce_settings_locations', array( 'WC_Helper_Settings', 'register_locations' ) );
		add_filter( 'woocommerce_settings_groups_test', array( 'WC_Helper_Settings', 'register_test_groups' ) );
	}

	/**
	 * Registers some example locations, including invalid ones that should not show up in JSON responses.
	 * @since  2.7.0
	 * @param  array $locations
	 * @return array
	 */
	public static function register_locations( $locations ) {
		$locations[] = array(
			'id'          => 'test',
			'type'        => 'page',
			'bad'         => 'value',
			'label'       => __( 'Test Extension', 'woocommerce' ),
			'description' => __( 'My awesome test settings.', 'woocommerce' ),
		);
		$locations[] = array(
			'id'          => 'test-2',
			'type'        => 'page',
			'label'       => __( 'Test Extension', 'woocommerce' ),
			'description' => '',
		);
		$locations[] = array(
			'id'    => 'coupon-data',
			'type'  => 'metabox',
			'label' => __( 'Coupon Data', 'woocommerce' ),
		);
		$locations[] = array(
			'label' => __( 'Invalid', 'woocommerce' ),
		);
		$locations[] = array(
			'id' => 'invalid',
		);
		return $locations;
	}

	/**
	 * Registers some example groups for the 'test' page.
	 * @since  2.7.0
	 * @param  array $groups
	 * @return array
	 */
	public static function register_test_groups( $groups ) {
		$groups[] = array(
			'id'    => 'general',
			'label' => __( 'General', 'woocommerce' ),
		);
		$groups[] = array(
			'id'    => 'inventory',
			'label' => __( 'Inventory', 'woocommerce' ),
		);
		return $groups;
	}

}
