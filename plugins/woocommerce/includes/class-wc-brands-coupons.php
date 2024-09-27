<?php

declare( strict_types = 1);

/**
 * WC_Brands_Coupons class.
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @version 9.4.0
 */
class WC_Brands_Coupons {

	const E_WC_COUPON_EXCLUDED_BRANDS = 301;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Coupon validation and error handling.
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_coupon_valid' ), 10, 3 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product' ), 10, 3 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'brand_exclusion_error' ), 10, 2 );
	}

	/**
	 * Validate the coupon based on included and/or excluded product brands.
	 *
	 * If one of the following conditions are met, an exception will be thrown and
	 * displayed as an error notice on the cart page:
	 *
	 * 1) Coupon has a brand requirement but no products in the cart have the brand.
	 * 2) All products in the cart match the brand exclusion rule.
	 * 3) For a cart discount, there is at least one product in cart that matches exclusion rule.
	 *
	 * @throws Exception Throws Exception for invalid coupons.
	 * @param  bool         $valid  Whether the coupon is valid.
	 * @param  WC_Coupon    $coupon Coupon object.
	 * @param  WC_Discounts $discounts Discounts object.
	 * @return bool         $valid  True if coupon is valid, otherwise Exception will be thrown.
	 */
	public function is_coupon_valid( $valid, $coupon, $discounts = null ) {
		$this->set_brand_settings_on_coupon( $coupon );

		// Only check if coupon has brand restrictions on it.
		$brand_coupon_settings = WC_Brands_Brand_Settings_Manager::get_brand_settings_on_coupon( $coupon );

		$brand_restrictions = ! empty( $brand_coupon_settings['included_brands'] ) || ! empty( $brand_coupon_settings['excluded_brands'] );
		if ( ! $brand_restrictions ) {
			return $valid;
		}

		$included_brands_match   = false;
		$excluded_brands_matches = 0;

		$items = $discounts->get_items();

		foreach ( $items as $item ) {
			$product_brands = $this->get_product_brands( $this->get_product_id( $item->product ) );

			if ( ! empty( array_intersect( $product_brands, $brand_coupon_settings['included_brands'] ) ) ) {
				$included_brands_match = true;
			}

			if ( ! empty( array_intersect( $product_brands, $brand_coupon_settings['excluded_brands'] ) ) ) {
				++$excluded_brands_matches;
			}
		}

		// 1) Coupon has a brand requirement but no products in the cart have the brand.
		if ( ! $included_brands_match && ! empty( $brand_coupon_settings['included_brands'] ) ) {
			throw new Exception( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_NOT_APPLICABLE ), WC_Coupon::E_WC_COUPON_NOT_APPLICABLE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// 2) All products in the cart match brand exclusion rule.
		if ( count( $items ) === $excluded_brands_matches ) {
			throw new Exception( __( 'Sorry, this coupon is not applicable to the brands of selected products.', 'woocommerce' ), self::E_WC_COUPON_EXCLUDED_BRANDS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// 3) For a cart discount, there is at least one product in cart that matches exclusion rule.
		if ( $coupon->is_type( 'fixed_cart' ) && $excluded_brands_matches > 0 ) {
			throw new Exception( __( 'Sorry, this coupon is not applicable to the brands of selected products.', 'woocommerce' ), self::E_WC_COUPON_EXCLUDED_BRANDS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $valid;
	}

	/**
	 * Check if a coupon is valid for a product.
	 *
	 * This allows percentage and product discounts to apply to only
	 * the correct products in the cart.
	 *
	 * @param  bool       $valid   Whether the product should get the coupon's discounts.
	 * @param  WC_Product $product WC Product Object.
	 * @param  WC_Coupon  $coupon  Coupon object.
	 * @return bool       $valid
	 */
	public function is_valid_for_product( $valid, $product, $coupon ) {

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $valid;
		}
		$this->set_brand_settings_on_coupon( $coupon );

		$product_id     = $this->get_product_id( $product );
		$product_brands = $this->get_product_brands( $product_id );

		// Check if coupon has a brand requirement and if this product has that brand attached.
		$brand_coupon_settings = WC_Brands_Brand_Settings_Manager::get_brand_settings_on_coupon( $coupon );
		if ( ! empty( $brand_coupon_settings['included_brands'] ) && empty( array_intersect( $product_brands, $brand_coupon_settings['included_brands'] ) ) ) {
			return false;
		}

		// Check if coupon has a brand exclusion and if this product has that brand attached.
		if ( ! empty( $brand_coupon_settings['excluded_brands'] ) && ! empty( array_intersect( $product_brands, $brand_coupon_settings['excluded_brands'] ) ) ) {
			return false;
		}

		return $valid;
	}

	/**
	 * Display a custom error message when a cart discount coupon does not validate
	 * because an excluded brand was found in the cart.
	 *
	 * @param  string $err      The error message.
	 * @param  string $err_code The error code.
	 * @return string
	 */
	public function brand_exclusion_error( $err, $err_code ) {
		if ( self::E_WC_COUPON_EXCLUDED_BRANDS !== $err_code ) {
			return $err;
		}

		return __( 'Sorry, this coupon is not applicable to the brands of selected products.', 'woocommerce' );
	}

	/**
	 * Get a list of brands that are assigned to a specific product
	 *
	 * @param  int $product_id Product id.
	 * @return array brands
	 */
	private function get_product_brands( $product_id ) {
		return wp_get_post_terms( $product_id, 'product_brand', array( 'fields' => 'ids' ) );
	}

	/**
	 * Set brand settings as properties on coupon object. These properties are
	 * lists of included product brand IDs and list of excluded brand IDs.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 *
	 * @return void
	 */
	private function set_brand_settings_on_coupon( $coupon ) {
		$brand_coupon_settings = WC_Brands_Brand_Settings_Manager::get_brand_settings_on_coupon( $coupon );

		if ( ! empty( $brand_coupon_settings['included_brands'] ) && ! empty( $brand_coupon_settings['excluded_brands'] ) ) {
			return;
		}

		$included_brands = get_post_meta( $coupon->get_id(), 'product_brands', true );
		if ( empty( $included_brands ) ) {
			$included_brands = array();
		}

		$excluded_brands = get_post_meta( $coupon->get_id(), 'exclude_product_brands', true );
		if ( empty( $excluded_brands ) ) {
			$excluded_brands = array();
		}

		// Store these for later to avoid multiple look-ups.
		WC_Brands_Brand_Settings_Manager::set_brand_settings_on_coupon( $coupon );
	}

	/**
	 * Returns the product (or variant) ID.
	 *
	 * @param  WC_Product $product WC Product Object.
	 * @return int Product ID
	 */
	private function get_product_id( $product ) {
		return $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	}
}

new WC_Brands_Coupons();
