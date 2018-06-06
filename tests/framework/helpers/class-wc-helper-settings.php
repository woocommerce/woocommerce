<?php

/**
 * Class WC_Helper_Settings.
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_Helper_Settings {

	/**
	 * Hooks in some dummy data for testing the settings REST API.
	 *
	 * @since 3.0.0
	 */
	public static function register() {
		add_filter( 'woocommerce_settings_groups', array( 'WC_Helper_Settings', 'register_groups' ) );
		add_filter( 'woocommerce_settings-test', array( 'WC_Helper_Settings', 'register_test_settings' ) );
	}

	/**
	 * Registers some example setting groups, including invalid ones that should not show up in JSON responses.
	 *
	 * @since 3.0.0
	 * @param  array $groups
	 * @return array
	 */
	public static function register_groups( $groups ) {
		$groups[] = array(
			'id'          => 'test',
			'bad'         => 'value',
			'label'       => 'Test extension',
			'description' => 'My awesome test settings.',
			'option_key'  => '',
		);
		$groups[] = array(
			'id'          => 'sub-test',
			'parent_id'   => 'test',
			'label'       => 'Sub test',
			'description' => '',
			'option_key'  => '',
		);
		$groups[] = array(
			'id'    => 'coupon-data',
			'label' => 'Coupon data',
			'option_key'  => '',
		);
		$groups[] = array(
			'id' => 'invalid',
			'option_key'  => '',
		);
		return $groups;
	}

	/**
	 * Registers some example settings.
	 *
	 * @since 3.0.0
	 * @param  array $settings
	 * @return array
	 */
	public static function register_test_settings( $settings ) {
		$settings[] = array(
			'id'          => 'woocommerce_shop_page_display',
			'label'       => 'Shop page display',
			'description' => 'This controls what is shown on the product archive.',
			'default'     => '',
			'type'        => 'select',
			'options'     => array(
				''              => 'Show products',
				'subcategories' => 'Show categories &amp; subcategories',
				'both'          => 'Show both',
			),
			'option_key'  => 'woocommerce_shop_page_display',
		);
		return $settings;
	}
}
