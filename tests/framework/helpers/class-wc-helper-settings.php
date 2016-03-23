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
		add_filter( 'woocommerce_settings_groups', array( 'WC_Helper_Settings', 'register_groups' ) );
		add_filter( 'woocommerce_settings_test', array( 'WC_Helper_Settings', 'register_test_settings' ) );
	}

	/**
	 * Registers some example setting groups, including invalid ones that should not show up in JSON responses.
	 * @since  2.7.0
	 * @param  array $groups
	 * @return array
	 */
	public static function register_groups( $groups ) {
		$groups[] = array(
			'id'          => 'test',
			'type'        => 'page',
			'bad'         => 'value',
			'label'       => __( 'Test Extension', 'woocommerce' ),
			'description' => __( 'My awesome test settings.', 'woocommerce' ),
		);
		$groups[] = array(
			'id'          => 'test-2',
			'type'        => 'page',
			'label'       => __( 'Test Extension', 'woocommerce' ),
			'description' => '',
		);
		$groups[] = array(
			'id'    => 'coupon-data',
			'type'  => 'metabox',
			'label' => __( 'Coupon Data', 'woocommerce' ),
		);
		$groups[] = array(
			'id' => 'invalid',
		);
		return $groups;
	}

	/**
	 * Registers some example settings.
	 * @since  2.7.0
	 * @param  array $settings
	 * @return array
	 */
	public static function register_settings( $settings ) {
		$settings[] = array(
			'id' 	=> 'catalog_options',
			'label' => __( 'Shop & Product Pages', 'woocommerce' ),
			'type' 	=> 'title',
		);
		$settings[] = array(
			'id'          => 'woocommerce_shop_page_display',
			'label'       => __( 'Shop Page Display', 'woocommerce' ),
			'description' => __( 'This controls what is shown on the product archive.', 'woocommerce' ),
			'default'     => '',
			'type'        => 'select',
			'options'     => array(
				''              => __( 'Show products', 'woocommerce' ),
				'subcategories' => __( 'Show categories &amp; subcategories', 'woocommerce' ),
				'both'          => __( 'Show both', 'woocommerce' ),
			),
		);
		$settings[] = array(
			'id'            => 'woocommerce_enable_lightbox',
			'label'         => __( 'Product Image Gallery', 'woocommerce' ),
			'description'   => __( 'Enable Lightbox for product images', 'woocommerce' ),
			'default'       => 'yes',
			'tip'           => __( 'Product gallery images will open in a lightbox.', 'woocommerce' ),
			'type'          => 'checkbox',
		);
		return $settings;
	}

}
