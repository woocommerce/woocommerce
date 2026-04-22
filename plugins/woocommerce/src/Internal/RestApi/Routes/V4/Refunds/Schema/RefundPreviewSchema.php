<?php
/**
 * RefundPreviewSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * Schema for the refund preview response.
 *
 * @since 10.8.0
 */
class RefundPreviewSchema extends AbstractSchema {

	/**
	 * Get an item response. Not used for preview — the controller returns the data array directly.
	 *
	 * @param mixed                              $item           Item data.
	 * @param WP_REST_Request<array<string,mixed>> $request        Request object.
	 * @param array                              $include_fields Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		return is_array( $item ) ? $item : array();
	}

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'refund-preview';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'The original order line item ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'name'         => array(
					'description' => __( 'The line item name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'quantity'     => array(
					'description' => __( 'The quantity being refunded.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'subtotal'     => array(
					'description' => __( 'The refund subtotal for this item (excluding tax).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'tax'          => array(
					'description' => __( 'The tax amount for this item.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'total'        => array(
					'description' => __( 'The total refund for this item (including tax).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'product_id'   => array(
					'description' => __( 'The product ID (products only).', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'variation_id' => array(
					'description' => __( 'The variation ID, if applicable (products only).', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
			),
		);

		$section_schema = array(
			'type'       => 'object',
			'properties' => array(
				'items'    => array(
					'description' => __( 'Line items in this section.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => $item_schema,
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'subtotal' => array(
					'description' => __( 'Section subtotal (excluding tax).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'tax'      => array(
					'description' => __( 'Section tax total.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'total'    => array(
					'description' => __( 'Section total (including tax).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
			),
		);

		return array(
			'breakdown'      => array(
				'description' => __( 'Refund breakdown by item type.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'properties'  => array(
					'products' => $section_schema,
					'shipping' => $section_schema,
					'fees'     => $section_schema,
				),
			),
			'subtotal'       => array(
				'description' => __( 'Grand subtotal of the refund preview (excluding tax).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'tax'            => array(
				'description' => __( 'Grand total tax of the refund preview.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'total'          => array(
				'description' => __( 'Grand total of the refund preview (including tax).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'max_refundable' => array(
				'description' => __( 'Maximum refundable amount remaining on the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
		);
	}
}
