<?php
/**
 * Order Item Schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\Utilities\ProductImages;

/**
 * OrderItemSchema class.
 */
class OrderItemSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'order_item';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'        => [
				'description' => __( 'The item product or variation ID.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
				'arg_options' => [
					'sanitize_callback' => 'absint',
					'validate_callback' => [ $this, 'product_id_exists' ],
				],
			],
			'quantity'  => [
				'description' => __( 'Quantity of this item in the cart.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
				'arg_options' => [
					'sanitize_callback' => 'wc_stock_amount',
				],
			],
			'name'      => [
				'description' => __( 'Product name.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'string', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sku'       => [
				'description' => __( 'Stock keeping unit, if applicable.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'string', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'permalink' => [
				'description' => __( 'Product URL.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'string', 'null' ],
				'format'      => 'uri',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'images'    => [
				'description' => __( 'List of images.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'        => [
							'description' => __( 'Image ID.', 'woo-gutenberg-products-block' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
						],
						'src'       => [
							'description' => __( 'Full size image URL.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => [ 'view', 'edit' ],
						],
						'thumbnail' => [
							'description' => __( 'Thumbnail URL.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => [ 'view', 'edit' ],
						],
						'srcset'    => [
							'description' => __( 'Thumbnail srcset for responsive images.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'sizes'     => [
							'description' => __( 'Thumbnail sizes for responsive images.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'name'      => [
							'description' => __( 'Image name.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'alt'       => [
							'description' => __( 'Image alternative text.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
			],
			'variation' => [
				'description' => __( 'Chosen attributes (for variations).', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'attribute' => [
							'description' => __( 'Variation attribute name.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'value'     => [
							'description' => __( 'Variation attribute value.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
			],
			'totals'    => [
				'description' => __( 'Item total amounts provided using the smallest unit of the currency.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'line_subtotal'     => [
							'description' => __( 'Line subtotal (the price of the product before coupon discounts have been applied).', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_subtotal_tax' => [
							'description' => __( 'Line subtotal tax.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_total'        => [
							'description' => __( 'Line total (the price of the product after coupon discounts have been applied).', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_total_tax'    => [
							'description' => __( 'Line total tax.', 'woo-gutenberg-products-block' ),
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
	 * Check given ID exists,
	 *
	 * @param integer $product_id Product ID.
	 * @return bool
	 */
	public function product_id_exists( $product_id ) {
		$post = get_post( (int) $product_id );
		return $post && in_array( $post->post_type, [ 'product', 'product_variation' ], true );
	}

	/**
	 * Convert an order item to an object suitable for the response.
	 *
	 * @param \WC_Order_Item_Product $line_item Order line item array.
	 * @return array
	 */
	public function get_item_response( \WC_Order_Item_Product $line_item ) {
		$product     = $line_item->get_product();
		$has_product = $product instanceof \WC_Product;

		return [
			'id'        => $line_item->get_variation_id() ? $line_item->get_variation_id() : $line_item->get_product_id(),
			'quantity'  => $line_item->get_quantity(),
			'name'      => $has_product ? $this->prepare_html_response( $product->get_title() ) : null,
			'sku'       => $has_product ? $this->prepare_html_response( $product->get_sku() ) : null,
			'permalink' => $has_product ? $product->get_permalink() : null,
			'images'    => $has_product ? ( new ProductImages() )->images_to_array( $product ) : [],
			'variation' => $has_product ? $this->format_variation_data( $line_item, $product ) : [],
			'totals'    => (object) array_merge(
				$this->get_store_currency_response(),
				[
					'line_subtotal'     => $this->prepare_money_response( $line_item->get_subtotal(), wc_get_price_decimals() ),
					'line_subtotal_tax' => $this->prepare_money_response( $line_item->get_subtotal_tax(), wc_get_price_decimals() ),
					'line_total'        => $this->prepare_money_response( $line_item->get_total(), wc_get_price_decimals() ),
					'line_total_tax'    => $this->prepare_money_response( $line_item->get_total_tax(), wc_get_price_decimals() ),
				]
			),
		];
	}

	/**
	 * Format variation data. For line items we get meta data and format it.
	 *
	 * @param \WC_Order_Item_Product $line_item Line item from the order.
	 * @param \WC_Product            $product Product data.
	 * @return array
	 */
	protected function format_variation_data( \WC_Order_Item_Product $line_item, \WC_Product $product ) {
		$return         = [];
		$line_item_meta = $line_item->get_meta_data();
		$attribute_keys = array_keys( $product->get_attributes() );

		foreach ( $line_item_meta as $meta ) {
			$key   = $meta->key;
			$value = $meta->value;

			if ( ! in_array( $key, $attribute_keys, true ) ) {
				continue;
			}

			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'pa_', '', urldecode( $key ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				// If this is a term slug, get the term's nice name.
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				// If this is a custom option slug, get the options name.
				$value = apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $product );
				$label = wc_attribute_label( $name, $product );
			}

			$return[] = [
				'attribute' => $this->prepare_html_response( $label ),
				'value'     => $this->prepare_html_response( $value ),
			];
		}

		return $return;
	}
}
