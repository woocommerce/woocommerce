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
 * @since 2.5.0
 */
class ProductSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product';

	/**
	 * Product schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'id'             => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'           => array(
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'variation'      => array(
				'description' => __( 'Product variation attributes, if applicable.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'permalink'      => array(
				'description' => __( 'Product URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description'    => array(
				'description' => __( 'Short description or excerpt from description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'on_sale'        => array(
				'description' => __( 'Is the product on sale?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sku'            => array(
				'description' => __( 'Unique identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'prices'         => array(
				'description' => __( 'Price data.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'currency_code'      => array(
							'description' => __( 'Currency code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'decimal_separator'  => array(
							'description' => __( 'Decimal separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'thousand_separator' => array(
							'description' => __( 'Thousand separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'decimals'           => array(
							'description' => __( 'Number of decimal places.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price_prefix'       => array(
							'description' => __( 'Price prefix, e.g. currency.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price_suffix'       => array(
							'description' => __( 'Price prefix, e.g. currency.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price'              => array(
							'description' => __( 'Current product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'regular_price'      => array(
							'description' => __( 'Regular product price', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'sale_price'         => array(
							'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price_range'        => array(
							'description' => __( 'Price range, if applicable.', 'woocommerce' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'min_amount' => array(
										'description' => __( 'Price amount.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'max_amount' => array(
										'description' => __( 'Price amount.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
					),
				),
			),
			'average_rating' => array(
				'description' => __( 'Reviews average rating.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'review_count'   => array(
				'description' => __( 'Amount of reviews that the product has.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'         => array(
				'description' => __( 'List of images.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Image ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'src'  => array(
							'description' => __( 'Image URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Image name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'alt'  => array(
							'description' => __( 'Image alternative text.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
			'has_options'    => array(
				'description' => __( 'Does the product have options?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_purchasable' => array(
				'description' => __( 'Is the product purchasable?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_in_stock'    => array(
				'description' => __( 'Is the product in stock?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'add_to_cart'    => array(
				'description' => __( 'Add to cart button parameters.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'text'        => array(
							'description' => __( 'Button text.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'description' => array(
							'description' => __( 'Button description.', 'woocommerce' ),
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
			'name'           => $this->prepare_html_response( $product->get_title() ),
			'variation'      => $this->prepare_html_response( $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, false ) : '' ),
			'permalink'      => $product->get_permalink(),
			'sku'            => $this->prepare_html_response( $product->get_sku() ),
			'description'    => $this->prepare_html_response( apply_filters( 'woocommerce_short_description', $product->get_short_description() ? $product->get_short_description() : wc_trim_string( $product->get_description(), 400 ) ) ),
			'on_sale'        => $product->is_on_sale(),
			'prices'         => $this->get_prices( $product ),
			'average_rating' => $product->get_average_rating(),
			'review_count'   => $product->get_review_count(),
			'images'         => ( new ProductImages() )->images_to_array( $product ),
			'has_options'    => $product->has_options(),
			'is_purchasable' => $product->is_purchasable(),
			'is_in_stock'    => $product->is_in_stock(),
			'add_to_cart'    => $this->prepare_html_response(
				[
					'text'        => $product->add_to_cart_text(),
					'description' => $product->add_to_cart_description(),
				]
			),
		];
	}

	/**
	 * Get an array of pricing data.
	 *
	 * @param \WC_Product|\WC_Product_Variation $product Product instance.
	 * @return array
	 */
	protected function get_prices( $product ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$position         = get_option( 'woocommerce_currency_pos' );
		$symbol           = html_entity_decode( get_woocommerce_currency_symbol() );
		$prefix           = '';
		$suffix           = '';

		// No break so symbol is added.
		switch ( $position ) {
			case 'left_space':
				$prefix = $symbol . ' ';
				break;
			case 'left':
				$prefix = $symbol;
				break;
			case 'right_space':
				$suffix = ' ' . $symbol;
				break;
			case 'right':
				$suffix = $symbol;
				break;
		}

		$prices = [
			'currency_code'      => get_woocommerce_currency(),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
			'price_prefix'       => $prefix,
			'price_suffix'       => $suffix,
		];

		$prices['price']         = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product );
		$prices['regular_price'] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $product, [ 'price' => $product->get_regular_price() ] ) : wc_get_price_excluding_tax( $product, [ 'price' => $product->get_regular_price() ] );
		$prices['sale_price']    = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $product, [ 'price' => $product->get_sale_price() ] ) : wc_get_price_excluding_tax( $product, [ 'price' => $product->get_sale_price() ] );
		$prices['price_range']   = $this->get_price_range( $product );

		return $prices;
	}

	/**
	 * Get price range from certain product types.
	 *
	 * @param \WC_Product|\WC_Product_Variation $product Product instance.
	 * @return arary|null
	 */
	protected function get_price_range( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );

			if ( min( $prices['price'] ) !== max( $prices['price'] ) ) {
				return [
					'min_amount' => min( $prices['price'] ),
					'max_amount' => max( $prices['price'] ),
				];
			}
		}

		if ( $product->is_type( 'grouped' ) ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );
			$price_function   = 'incl' === $tax_display_mode ? 'wc_get_price_including_tax' : 'wc_get_price_excluding_tax';

			foreach ( $children as $child ) {
				if ( '' !== $child->get_price() ) {
					$child_prices[] = $price_function( $child );
				}
			}

			if ( ! empty( $child_prices ) ) {
				return [
					'min_amount' => min( $child_prices ),
					'max_amount' => max( $child_prices ),
				];
			}
		}

		return null;
	}
}
