<?php
/**
 * Cart Coupon Schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * CartCouponSchema class.
 *
 * @since 2.5.0
 */
class CartCouponSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart_coupon';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'code'   => [
				'description' => __( 'The coupons unique code.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'arg_options' => [
					'sanitize_callback' => 'wc_format_coupon_code',
					'validate_callback' => [ $this, 'coupon_exists' ],
				],
			],
			'totals' => [
				'description' => __( 'Total amounts provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'total_discount'     => [
							'description' => __( 'Total discount applied by this coupon.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_discount_tax' => [
							'description' => __( 'Total tax removed due to discount applied by this coupon.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					]
				),
			],
		];
	}

	/**
	 * Check given coupon exists.
	 *
	 * @param string $coupon_code Coupon code.
	 * @return bool
	 */
	public function coupon_exists( $coupon_code ) {
		$coupon = new \WC_Coupon( $coupon_code );
		return (bool) $coupon->get_id() || $coupon->get_virtual();
	}

	/**
	 * Convert a WooCommerce cart item to an object suitable for the response.
	 *
	 * @param string $coupon_code Coupon code from the cart.
	 * @return array
	 */
	public function get_item_response( $coupon_code ) {
		$controller           = new CartController();
		$cart                 = $controller->get_cart_instance();
		$total_discounts      = $cart->get_coupon_discount_totals();
		$total_discount_taxes = $cart->get_coupon_discount_tax_totals();
		return [
			'code'   => $coupon_code,
			'totals' => (object) array_merge(
				$this->get_store_currency_response(),
				[
					'total_discount'     => $this->prepare_money_response( isset( $total_discounts[ $coupon_code ] ) ? $total_discounts[ $coupon_code ] : 0, wc_get_price_decimals() ),
					'total_discount_tax' => $this->prepare_money_response( isset( $total_discount_taxes[ $coupon_code ] ) ? $total_discount_taxes[ $coupon_code ] : 0, wc_get_price_decimals(), PHP_ROUND_HALF_DOWN ),
				]
			),
		];
	}
}
