<?php
//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

declare( strict_types = 1);

/**
 * Brand settings manager.
 *
 * This class is responsible for setting and getting brand settings for a coupon.
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @version 9.4.0
 */
class WC_Brands_Brand_Settings_Manager {
	/**
	 * Brand settings for a coupon.
	 *
	 * @var array
	 */
	private static $brand_settings = array();

	/**
	 * Set brand settings for a coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 */
	public static function set_brand_settings_on_coupon( $coupon ) {
		$coupon_id = $coupon->get_id();

		// Check if the brand settings are already set for this coupon.
		if ( isset( self::$brand_settings[ $coupon_id ] ) ) {
			return;
		}

		$included_brands = get_post_meta( $coupon_id, 'product_brands', true );
		$included_brands = ! empty( $included_brands ) ? $included_brands : array();

		$excluded_brands = get_post_meta( $coupon_id, 'exclude_product_brands', true );
		$excluded_brands = ! empty( $excluded_brands ) ? $excluded_brands : array();

		// Store these settings in the static array.
		self::$brand_settings[ $coupon_id ] = array(
			'included_brands' => $included_brands,
			'excluded_brands' => $excluded_brands,
		);
	}

	/**
	 * Get brand settings for a coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 * @return array Brand settings (included and excluded brands).
	 */
	public static function get_brand_settings_on_coupon( $coupon ) {
		$coupon_id = $coupon->get_id();

		if ( isset( self::$brand_settings[ $coupon_id ] ) ) {
			return self::$brand_settings[ $coupon_id ];
		}

		// Default return value if no settings are found.
		return array(
			'included_brands' => array(),
			'excluded_brands' => array(),
		);
	}
}
