<?php
/**
 * Abstract Schema.
 *
 * Rest API schema class.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\Utilities\ProductImages;

/**
 * AbstractBlock class.
 *
 * @since 2.5.0
 */
class CartItemSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart_item';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'key'        => array(
				'description' => __( 'Unique identifier for the item within the cart.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'id'         => array(
				'description' => __( 'The cart item product or variation ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
				'arg_options' => array(
					'sanitize_callback' => 'absint',
					'validate_callback' => array( $this, 'product_id_exists' ),
				),
			),
			'quantity'   => array(
				'description' => __( 'Quantity of this item in the cart.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
				'arg_options' => array(
					'sanitize_callback' => 'wc_stock_amount',
				),
			),
			'name'       => array(
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sku'        => array(
				'description' => __( 'Stock keeping unit, if applicable.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'permalink'  => array(
				'description' => __( 'Product URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'     => array(
				'description' => __( 'List of images.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Image ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'src'  => array(
							'description' => __( 'Image URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name' => array(
							'description' => __( 'Image name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'alt'  => array(
							'description' => __( 'Image alternative text.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'price'      => array(
				'description' => __( 'Current product price.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'line_price' => array(
				'description' => __( 'Current line price.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'variation'  => array(
				'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'attribute' => array(
							'description' => __( 'Variation attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'value'     => array(
							'description' => __( 'Variation attribute value.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
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
	 * Convert a WooCommerce cart item to an object suitable for the response.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	public function get_item_response( $cart_item ) {
		$product = $cart_item['data'];
		return [
			'key'        => $cart_item['key'],
			'id'         => $product->get_id(),
			'quantity'   => wc_stock_amount( $cart_item['quantity'] ),
			'name'       => $this->prepare_html_response( $product->get_title() ),
			'sku'        => $this->prepare_html_response( $product->get_sku() ),
			'permalink'  => $product->get_permalink(),
			'images'     => ( new ProductImages() )->images_to_array( $product ),
			'price'      => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
			'line_price' => wc_format_decimal( isset( $cart_item['line_total'] ) ? $cart_item['line_total'] : $product->get_price() * wc_stock_amount( $cart_item['quantity'] ), wc_get_price_decimals() ),
			'variation'  => $this->format_variation_data( $cart_item['variation'], $product ),
		];
	}

	/**
	 * Format variation data, for example convert slugs such as attribute_pa_size to Size.
	 *
	 * @param array       $variation_data Array of data from the cart.
	 * @param \WC_Product $product Product data.
	 * @return array
	 */
	protected function format_variation_data( $variation_data, $product ) {
		$return = [];

		foreach ( $variation_data as $key => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $key ) ) );

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
				$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $product );
			}

			$return[ $this->prepare_html_response( $label ) ] = $this->prepare_html_response( $value );
		}

		return $return;
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @param string      $slug   Taxonomy name.
	 * @param \WC_Product $object Product data.
	 * @return string
	 */
	protected function get_attribute_taxonomy_name( $slug, $object ) {
		// Format slug so it matches attributes of the product.
		$slug       = wc_attribute_taxonomy_slug( $slug );
		$attributes = $object->get_attributes();
		$attribute  = false;

		// pa_ attributes.
		if ( isset( $attributes[ wc_attribute_taxonomy_name( $slug ) ] ) ) {
			$attribute = $attributes[ wc_attribute_taxonomy_name( $slug ) ];
		} elseif ( isset( $attributes[ $slug ] ) ) {
			$attribute = $attributes[ $slug ];
		}

		if ( ! $attribute ) {
			return $slug;
		}

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	}
}
