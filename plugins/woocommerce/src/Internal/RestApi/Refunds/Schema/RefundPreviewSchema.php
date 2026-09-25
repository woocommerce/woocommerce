<?php
/**
 * RefundPreviewSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Refunds\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Schema for the refund preview response, shared by the wc/v3 and wc/v4 preview endpoints.
 * Standalone (no V4 AbstractSchema parent) so wc/v3 does not depend on the v4 route tree.
 *
 * @since 10.9.0
 */
class RefundPreviewSchema {

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'refund-preview';

	/**
	 * Context for the item schema - view, edit, and embed.
	 *
	 * @var array
	 */
	const VIEW_EDIT_EMBED_CONTEXT = array( 'view', 'edit', 'embed' );

	/**
	 * Get the item schema.
	 *
	 * @return array The item schema.
	 * @since 10.9.0
	 */
	public function get_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => static::IDENTIFIER,
			'type'       => 'object',
			'properties' => $this->get_item_schema_properties(),
		);
	}

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public function get_item_schema_properties(): array {
		return array(
			'breakdown'      => array(
				'description' => __( 'Refund breakdown by item type.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'properties'  => array(
					'products' => $this->get_section_schema( 'products' ),
					'shipping' => $this->get_section_schema( 'shipping' ),
					'fees'     => $this->get_section_schema( 'fees' ),
				),
			),
			'subtotal'       => array(
				'description' => __( 'Grand subtotal of the refund preview (excluding tax).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'tax'            => array(
				'description' => __( 'Grand tax total of the refund preview.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'total'          => array(
				'description' => __( 'Grand total of the refund preview (tax-inclusive).', 'woocommerce' ),
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

	/**
	 * Schema for one section of the breakdown (products, shipping, or fees).
	 *
	 * @param string $section_key One of 'products', 'shipping', 'fees'. Determines which item schema variant is used.
	 * @return array
	 */
	private function get_section_schema( string $section_key ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'items'    => array(
					'description' => __( 'Line items in this section.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => 'products' === $section_key ? $this->get_product_item_schema() : $this->get_base_item_schema(),
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
					'description' => __( 'Section total (tax-inclusive).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Schema for an item entry in the shipping or fees sections.
	 *
	 * @return array
	 */
	private function get_base_item_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'       => array(
					'description' => __( 'The original order line item ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'name'     => array(
					'description' => __( 'The line item name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'quantity' => array(
					'description' => __( 'The quantity being refunded. Null when the refund was specified by amount only.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'subtotal' => array(
					'description' => __( 'The refund subtotal for this item (excluding tax).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'tax'      => array(
					'description' => __( 'The tax amount for this item.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
				'total'    => array(
					'description' => __( 'The refund total for this item (tax-inclusive).', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Schema for an item entry in the products section (extends the base with product_id).
	 *
	 * @return array
	 */
	private function get_product_item_schema(): array {
		$schema                             = $this->get_base_item_schema();
		$schema['properties']['product_id'] = array(
			'description' => __( 'Product or variation ID.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			'readonly'    => true,
		);
		return $schema;
	}
}
