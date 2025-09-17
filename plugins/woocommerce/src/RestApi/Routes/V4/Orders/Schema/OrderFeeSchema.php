<?php
/**
 * OrderFeeSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Order_Item_Fee;
use WP_REST_Request;

/**
 * OrderFeeSchema class.
 */
class OrderFeeSchema extends AbstractLineItemSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-fee';

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
			'id'         => array(
				'description' => __( 'Item ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'name'       => array(
				'description' => __( 'Fee name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'tax_class'  => array(
				'description' => __( 'Tax class of fee.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'tax_status' => array(
				'description' => __( 'Tax status of fee.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'enum'        => array( 'taxable', 'none' ),
			),
			'total'      => array(
				'description' => __( 'Line total (after discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'total_tax'  => array(
				'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'taxes'      => $this->get_taxes_schema(),
			'meta_data'  => $this->get_meta_data_schema(),
		);

		return $schema;
	}

	/**
	 * Get an item response.
	 *
	 * @param WC_Order_Item_Fee $order_item Order item instance.
	 * @param WP_REST_Request   $request Request object.
	 * @param array             $include_fields Fields to include in the response.
	 * @return array
	 */
	public function get_item_response( $order_item, WP_REST_Request $request, array $include_fields = array() ): array {
		$dp   = is_null( $request['num_decimals'] ) ? wc_get_price_decimals() : absint( $request['num_decimals'] );
		$data = array(
			'id'         => $order_item->get_id(),
			'name'       => $order_item->get_name(),
			'tax_class'  => $order_item->get_tax_class(),
			'tax_status' => $order_item->get_tax_status(),
			'total'      => wc_format_decimal( $order_item->get_total(), $dp ),
			'total_tax'  => wc_format_decimal( $order_item->get_total_tax(), $dp ),
			'taxes'      => $this->prepare_taxes( $order_item, $request ),
			'meta_data'  => $this->prepare_meta_data( $order_item ),
		);

		return $data;
	}
}
