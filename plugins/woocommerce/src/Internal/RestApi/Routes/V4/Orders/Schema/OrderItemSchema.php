<?php
/**
 * OrderItemSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order_Item_Product;
use WP_REST_Request;
use WC_Product;

/**
 * OrderItemSchema class.
 */
class OrderItemSchema extends AbstractLineItemSchema {
	use CogsAwareTrait;

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-item';

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
			'id'           => array(
				'description' => __( 'Item ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'name'         => array(
				'description' => __( 'Item name.', 'woocommerce' ),
				'type'        => array( 'string', 'null' ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'image'        => array(
				'description' => __( 'Line item image, if available.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'product_id'   => array(
				'description' => __( 'Product or variation ID.', 'woocommerce' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'product_data' => array(
				'description' => __( 'Product data this item is linked to.', 'woocommerce' ),
				'type'        => array( 'object', 'null' ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'properties'  => $this->get_product_data_schema(),
			),
			'quantity'     => array(
				'description' => __( 'Quantity ordered.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'price'        => array(
				'description' => __( 'Item price. Calculated as total / quantity.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'tax_class'    => array(
				'description' => __( 'Tax class of product.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'subtotal'     => array(
				'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'subtotal_tax' => array(
				'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'total'        => array(
				'description' => __( 'Line total (after discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'total_tax'    => array(
				'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'taxes'        => $this->get_taxes_schema(),
			'meta_data'    => $this->get_meta_data_schema(),
		);

		if ( $this->cogs_is_enabled() ) {
			$schema = $this->add_cogs_related_schema( $schema );
		}

		return $schema;
	}

	/**
	 * Add the Cost of Goods Sold related fields to the schema.
	 *
	 * @param array $schema The original schema.
	 * @return array The updated schema.
	 */
	private function add_cogs_related_schema( array $schema ): array {
		$schema['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data. Only present for product line items.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Value of the Cost of Goods Sold for the order item.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				),
			),
		);
		return $schema;
	}

	/**
	 * Get an item response.
	 *
	 * @param WC_Order_Item_Product $order_item Order item instance.
	 * @param WP_REST_Request       $request Request object.
	 * @param array                 $include_fields Fields to include in the response.
	 * @return array
	 */
	public function get_item_response( $order_item, WP_REST_Request $request, array $include_fields = array() ): array {
		$dp              = is_null( $request['num_decimals'] ) ? wc_get_price_decimals() : absint( $request['num_decimals'] );
		$quantity_amount = (float) $order_item->get_quantity();
		$data            = array(
			'id'           => $order_item->get_id(),
			'name'         => $order_item->get_name(),
			'image'        => $this->get_image( $order_item ),
			'product_id'   => $order_item->get_variation_id() ? $order_item->get_variation_id() : $order_item->get_product_id(),
			'product_data' => $this->get_product_data( $order_item ),
			'quantity'     => $order_item->get_quantity(),
			'price'        => $quantity_amount ? $order_item->get_total() / $quantity_amount : 0,
			'tax_class'    => $order_item->get_tax_class(),
			'subtotal'     => wc_format_decimal( $order_item->get_subtotal(), $dp ),
			'subtotal_tax' => wc_format_decimal( $order_item->get_subtotal_tax(), $dp ),
			'total'        => wc_format_decimal( $order_item->get_total(), $dp ),
			'total_tax'    => wc_format_decimal( $order_item->get_total_tax(), $dp ),
			'taxes'        => $this->prepare_taxes( $order_item, $request ),
			'meta_data'    => $this->prepare_meta_data( $order_item ),
		);

		// Add COGS data.
		if ( self::cogs_is_enabled() ) {
			$data['cost_of_goods_sold']['total_value'] = isset( $data['cogs_value'] ) ? $data['cogs_value'] : 0;
			unset( $data['cogs_value'] );
		}

		return $data;
	}

	/**
	 * Get embedded product schema.
	 *
	 * @return array
	 */
	private function get_product_data_schema(): array {
		return array(
			'name'             => array(
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'permalink'        => array(
				'description' => __( 'Product permalink.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'sku'              => array(
				'description' => __( 'Product SKU.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'global_unique_id' => array(
				'description' => __( 'Product global unique ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'type'             => array(
				'description' => __( 'Product type.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'is_virtual'       => array(
				'description' => __( 'Product is virtual.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'is_downloadable'  => array(
				'description' => __( 'Product is downloadable.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'needs_shipping'   => array(
				'description' => __( 'Product needs shipping.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
		);
	}

	/**
	 * Get product data.
	 *
	 * @param WC_Order_Item_Product $order_item Order item instance.
	 * @return array|null
	 */
	private function get_product_data( WC_Order_Item_Product $order_item ) {
		$product = $order_item->get_product();

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		return array(
			'name'             => $product->get_name(),
			'permalink'        => $product->get_permalink(),
			'sku'              => $product->get_sku(),
			'global_unique_id' => $product->get_global_unique_id(),
			'type'             => $product->get_type(),
			'is_virtual'       => $product->is_virtual(),
			'is_downloadable'  => $product->is_downloadable(),
			'needs_shipping'   => $product->needs_shipping(),
		);
	}

	/**
	 * Get image.
	 *
	 * @param WC_Order_Item_Product $order_item Order item instance.
	 * @return string
	 */
	private function get_image( WC_Order_Item_Product $order_item ) {
		$product = $order_item->get_product();

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$image_id = $product->get_image_id() ? $product->get_image_id() : 0;
		return $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
	}
}
