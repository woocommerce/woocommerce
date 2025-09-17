<?php
/**
 * AbstractLineItemSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;
use WC_Order_Item;
use WP_REST_Request;

/**
 * AbstractLineItemSchema class.
 */
abstract class AbstractLineItemSchema extends AbstractSchema {
	/**
	 * Get the meta data schema shared by all line item schemas.
	 *
	 * @return array
	 */
	protected function get_meta_data_schema(): array {
		return array(
			'description' => __( 'Meta data.', 'woocommerce' ),
			'type'        => 'array',
			'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'            => array(
						'description' => __( 'Meta ID.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						'readonly'    => true,
					),
					'key'           => array(
						'description' => __( 'Meta key.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'value'         => array(
						'description' => __( 'Meta value.', 'woocommerce' ),
						'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'display_key'   => array(
						'description' => __( 'Meta key for UI display.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'display_value' => array(
						'description' => __( 'Meta value for UI display.', 'woocommerce' ),
						'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
				),
			),
		);
	}

	/**
	 * Prepare the meta data for the order item.
	 *
	 * @param WC_Order_Item $order_item Order item instance.
	 * @return array
	 */
	protected function prepare_meta_data( $order_item ) {
		$formatted_meta_data = $order_item->get_all_formatted_meta_data( null );
		$return              = array();

		foreach ( $formatted_meta_data as $meta_id => $meta ) {
			$return[] = array(
				'id'            => $meta_id,
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => wc_clean( $meta->display_key ),
				'display_value' => wc_clean( $meta->display_value ),
			);
		}

		return $return;
	}

	/**
	 * Get the taxes schema shared by line item schemas.
	 *
	 * @return array
	 */
	protected function get_taxes_schema(): array {
		return array(
			'description' => __( 'Line taxes.', 'woocommerce' ),
			'type'        => 'array',
			'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			'readonly'    => true,
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array(
						'description' => __( 'Tax rate ID.', 'woocommerce' ),
						'type'        => 'integer',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						'readonly'    => true,
					),
					'total'    => array(
						'description' => __( 'Tax total.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						'readonly'    => true,
					),
					'subtotal' => array(
						'description' => __( 'Tax subtotal.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						'readonly'    => true,
					),
				),
			),
		);
	}

	/**
	 * Prepare the taxes for the order item.
	 *
	 * @param WC_Order_Item_Product|WC_Order_Item_Fee $order_item Order item instance.
	 * @param WP_REST_Request                         $request Request object.
	 * @return array
	 */
	protected function prepare_taxes( $order_item, WP_REST_Request $request ) {
		$taxes  = $order_item->get_taxes();
		$dp     = is_null( $request['num_decimals'] ) ? wc_get_price_decimals() : absint( $request['num_decimals'] );
		$return = array();

		if ( $taxes && ! empty( $taxes['total'] ) ) {
			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$return[] = array(
					'id'       => $tax_rate_id,
					'total'    => wc_format_decimal( $tax, $dp ),
					'subtotal' => wc_format_decimal( $taxes['subtotal'][ $tax_rate_id ] ?? $tax, $dp ),
				);
			}
		}

		return $return;
	}
}
