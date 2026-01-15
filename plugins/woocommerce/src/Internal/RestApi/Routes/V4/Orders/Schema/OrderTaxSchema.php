<?php
/**
 * OrderTaxSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Order_Item_Tax;
use WP_REST_Request;

/**
 * OrderFeeSchema class.
 */
class OrderTaxSchema extends AbstractLineItemSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-tax';

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
			'id'                 => array(
				'description' => __( 'Item ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'rate_code'          => array(
				'description' => __( 'Tax rate code.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'rate_id'            => array(
				'description' => __( 'Tax rate ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'label'              => array(
				'description' => __( 'Tax rate label.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'compound'           => array(
				'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'tax_total'          => array(
				'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'shipping_tax_total' => array(
				'description' => __( 'Shipping tax total.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'meta_data'          => $this->get_meta_data_schema(),
		);

		return $schema;
	}

	/**
	 * Get an item response.
	 *
	 * @param WC_Order_Item_Tax $order_item Order item instance.
	 * @param WP_REST_Request   $request Request object.
	 * @param array             $include_fields Fields to include in the response.
	 * @return array
	 */
	public function get_item_response( $order_item, WP_REST_Request $request, array $include_fields = array() ): array {
		$dp   = is_null( $request['num_decimals'] ) ? wc_get_price_decimals() : absint( $request['num_decimals'] );
		$data = array(
			'id'                 => $order_item->get_id(),
			'rate_code'          => $order_item->get_rate_code(),
			'rate_id'            => $order_item->get_rate_id(),
			'label'              => $order_item->get_label(),
			'compound'           => $order_item->get_compound(),
			'tax_total'          => wc_format_decimal( $order_item->get_tax_total(), $dp ),
			'shipping_tax_total' => wc_format_decimal( $order_item->get_shipping_tax_total(), $dp ),
			'meta_data'          => $this->prepare_meta_data( $order_item ),
		);

		return $data;
	}
}
