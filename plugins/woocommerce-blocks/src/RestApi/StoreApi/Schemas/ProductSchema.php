<?php
/**
 * Product Schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\Utilities\ProductImages;

/**
 * ProductSchema class.
 *
 * @since $VID:$
 */
class ProductSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'id'             => array(
				'description' => __( 'Unique identifier for the resource.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'           => array(
				'description' => __( 'Product name.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'variation'      => array(
				'description' => __( 'Product variation attributes, if applicable.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'permalink'      => array(
				'description' => __( 'Product URL.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description'    => array(
				'description' => __( 'Short description or excerpt from description.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'sku'            => array(
				'description' => __( 'Unique identifier.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'price'          => array(
				'description' => __( 'Current product price.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'price_html'     => array(
				'description' => __( 'Price formatted in HTML.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'average_rating' => array(
				'description' => __( 'Reviews average rating.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'review_count'   => array(
				'description' => __( 'Amount of reviews that the product has.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'         => array(
				'description' => __( 'List of images.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Image ID.', 'woo-gutenberg-products-block' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'src'  => array(
							'description' => __( 'Image URL.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Image name.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'alt'  => array(
							'description' => __( 'Image alternative text.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
			'has_options'    => array(
				'description' => __( 'Does the product have options?', 'woo-gutenberg-products-block' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_purchasable' => array(
				'description' => __( 'Is the product purchasable?', 'woo-gutenberg-products-block' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_in_stock'    => array(
				'description' => __( 'Is the product in stock?', 'woo-gutenberg-products-block' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'add_to_cart'    => array(
				'description' => __( 'Add to cart button parameters.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'text'        => array(
							'description' => __( 'Button text.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'description' => array(
							'description' => __( 'Button description.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),

		];
	}

	/**
	 * Convert a WooCommerce product into an object suitable for the response.
	 *
	 * @param array $product Product object.
	 * @return array
	 */
	public function get_item_response( $product ) {
		return [
			'id'             => $product->get_id(),
			'name'           => $product->get_title(),
			'variation'      => $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, false ) : '',
			'permalink'      => $product->get_permalink(),
			'sku'            => $product->get_sku(),
			'description'    => apply_filters( 'woocommerce_short_description', $product->get_short_description() ? $product->get_short_description() : wc_trim_string( $product->get_description(), 400 ) ),
			'price'          => $product->get_price(),
			'price_html'     => $product->get_price_html(),
			'average_rating' => $product->get_average_rating(),
			'review_count'   => $product->get_review_count(),
			'images'         => ( new ProductImages() )->images_to_array( $product ),
			'has_options'    => $product->has_options(),
			'is_purchasable' => $product->is_purchasable(),
			'is_in_stock'    => $product->is_in_stock(),
			'add_to_cart'    => [
				'text'        => $product->add_to_cart_text(),
				'description' => $product->add_to_cart_description(),
			],
		];
	}
}
