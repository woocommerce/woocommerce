<?php
/**
 * OrderCouponSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Order_Item_Coupon;
use WP_REST_Request;
use WC_Coupon;

/**
 * OrderCouponSchema class.
 */
class OrderCouponSchema extends AbstractLineItemSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-coupon';

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$schema = array(
			'id'             => array(
				'description' => __( 'Item ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'code'           => array(
				'description' => __( 'Coupon code.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'discount'       => array(
				'description' => __( 'Discount total.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'discount_tax'   => array(
				'description' => __( 'Discount total tax.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'discount_type'  => array(
				'description' => __( 'Discount type.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'nominal_amount' => array(
				'description' => __( 'Discount amount as defined in the coupon (absolute value or a percent, depending on the discount type).', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'free_shipping'  => array(
				'description' => __( 'Whether the coupon grants free shipping or not.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'meta_data'      => $this->get_meta_data_schema(),
		);

		return $schema;
	}

	/**
	 * Get an item response.
	 *
	 * @param WC_Order_Item_Coupon $order_item Order item instance.
	 * @param WP_REST_Request      $request Request object.
	 * @param array                $include_fields Fields to include in the response.
	 * @return array
	 */
	public function get_item_response( $order_item, WP_REST_Request $request, array $include_fields = array() ): array {
		$dp          = is_null( $request['num_decimals'] ) ? wc_get_price_decimals() : absint( $request['num_decimals'] );
		$temp_coupon = new WC_Coupon();
		$coupon_info = $order_item->get_meta( 'coupon_info', true );
		if ( $coupon_info ) {
			$temp_coupon->set_short_info( $coupon_info );
		} else {
			$coupon_meta = $order_item->get_meta( 'coupon_data', true );
			if ( $coupon_meta ) {
				$temp_coupon->set_props( (array) $coupon_meta );
			}
		}

		$data = array(
			'id'             => $order_item->get_id(),
			'code'           => $order_item->get_code(),
			'discount'       => wc_format_decimal( $order_item->get_discount(), $dp ),
			'discount_tax'   => wc_format_decimal( $order_item->get_discount_tax(), $dp ),
			'discount_type'  => $temp_coupon->get_discount_type(),
			'nominal_amount' => (float) $temp_coupon->get_amount(),
			'free_shipping'  => $temp_coupon->get_free_shipping(),
			'meta_data'      => $this->prepare_meta_data( $order_item ),
		);

		return $data;
	}
}
