<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;

/**
 * CartItemSchema class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @since 2.5.0
 */
class CartItemSchema extends ProductSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart_item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-item';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'key'                  => [
				'description' => __( 'Unique identifier for the item within the cart.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'id'                   => [
				'description' => __( 'The cart item product or variation ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'quantity'             => [
				'description' => __( 'Quantity of this item in the cart.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'quantity_limit'       => [
				'description' => __( 'The maximum quantity than can be added to the cart at once.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'name'                 => [
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'short_description'    => [
				'description' => __( 'Product short description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'description'          => [
				'description' => __( 'Product full description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sku'                  => [
				'description' => __( 'Stock keeping unit, if applicable.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'low_stock_remaining'  => [
				'description' => __( 'Quantity left in stock if stock is low, or null if not applicable.', 'woocommerce' ),
				'type'        => [ 'integer', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'backorders_allowed'   => [
				'description' => __( 'True if backorders are allowed past stock availability.', 'woocommerce' ),
				'type'        => [ 'boolean' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'show_backorder_badge' => [
				'description' => __( 'True if the product is on backorder.', 'woocommerce' ),
				'type'        => [ 'boolean' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sold_individually'    => [
				'description' => __( 'If true, only one item of this product is allowed for purchase in a single order.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'permalink'            => [
				'description' => __( 'Product URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'images'               => [
				'description' => __( 'List of images.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				],
			],
			'variation'            => [
				'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'attribute' => [
							'description' => __( 'Variation attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'value'     => [
							'description' => __( 'Variation attribute value.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'item_data'            => [
				'description' => __( 'Metadata related to the cart item', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'name'    => [
							'description' => __( 'Name of the metadata.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'value'   => [
							'description' => __( 'Value of the metadata.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'display' => [
							'description' => __( 'Optionally, how the metadata value should be displayed to the user.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'prices'               => [
				'description' => __( 'Price data for the product in the current line item, including or excluding taxes based on the "display prices during cart and checkout" setting. Provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'price'         => [
							'description' => __( 'Current product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'regular_price' => [
							'description' => __( 'Regular product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'sale_price'    => [
							'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'price_range'   => [
							'description' => __( 'Price range, if applicable.', 'woocommerce' ),
							'type'        => [ 'object', 'null' ],
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'properties'  => [
								'min_amount' => [
									'description' => __( 'Price amount.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'max_amount' => [
									'description' => __( 'Price amount.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
							],
						],
						'raw_prices'    => [
							'description' => __( 'Raw unrounded product prices used in calculations. Provided using a higher unit of precision than the currency.', 'woocommerce' ),
							'type'        => [ 'object', 'null' ],
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'properties'  => [
								'precision'     => [
									'description' => __( 'Decimal precision of the returned prices.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'price'         => [
									'description' => __( 'Current product price.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'regular_price' => [
									'description' => __( 'Regular product price.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'sale_price'    => [
									'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
							],
						],
					]
				),
			],
			'totals'               => [
				'description' => __( 'Item total amounts provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'line_subtotal'     => [
							'description' => __( 'Line subtotal (the price of the product before coupon discounts have been applied).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_subtotal_tax' => [
							'description' => __( 'Line subtotal tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_total'        => [
							'description' => __( 'Line total (the price of the product after coupon discounts have been applied).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'line_total_tax'    => [
							'description' => __( 'Line total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					]
				),
			],
			'catalog_visibility'   => [
				'description' => __( 'Whether the product is visible in the catalog', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			self::EXTENDING_KEY    => $this->get_extended_schema( self::IDENTIFIER ),
		];
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
			'key'                  => $cart_item['key'],
			'id'                   => $product->get_id(),
			'quantity'             => wc_stock_amount( $cart_item['quantity'] ),
			'quantity_limit'       => $this->get_product_quantity_limit( $product ),
			'name'                 => $this->prepare_html_response( $product->get_title() ),
			'short_description'    => $this->prepare_html_response( wc_format_content( $product->get_short_description() ) ),
			'description'          => $this->prepare_html_response( wc_format_content( $product->get_description() ) ),
			'sku'                  => $this->prepare_html_response( $product->get_sku() ),
			'low_stock_remaining'  => $this->get_low_stock_remaining( $product ),
			'backorders_allowed'   => (bool) $product->backorders_allowed(),
			'show_backorder_badge' => (bool) $product->backorders_require_notification() && $product->is_on_backorder( $cart_item['quantity'] ),
			'sold_individually'    => $product->is_sold_individually(),
			'permalink'            => $product->get_permalink(),
			'images'               => $this->get_images( $product ),
			'variation'            => $this->format_variation_data( $cart_item['variation'], $product ),
			'item_data'            => $this->get_item_data( $cart_item ),
			'prices'               => (object) $this->prepare_product_price_response( $product, get_option( 'woocommerce_tax_display_cart' ) ),
			'totals'               => (object) $this->prepare_currency_response(
				[
					'line_subtotal'     => $this->prepare_money_response( $cart_item['line_subtotal'], wc_get_price_decimals() ),
					'line_subtotal_tax' => $this->prepare_money_response( $cart_item['line_subtotal_tax'], wc_get_price_decimals() ),
					'line_total'        => $this->prepare_money_response( $cart_item['line_total'], wc_get_price_decimals() ),
					'line_total_tax'    => $this->prepare_money_response( $cart_item['line_tax'], wc_get_price_decimals() ),
				]
			),
			'catalog_visibility'   => $product->get_catalog_visibility(),
			self::EXTENDING_KEY    => $this->get_extended_data( self::IDENTIFIER, $cart_item ),
		];
	}

	/**
	 * Get an array of pricing data.
	 *
	 * @param \WC_Product $product Product instance.
	 * @param string      $tax_display_mode If returned prices are incl or excl of tax.
	 * @return array
	 */
	protected function prepare_product_price_response( \WC_Product $product, $tax_display_mode = '' ) {
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );
		$price_function   = $this->get_price_function_from_tax_display_mode( $tax_display_mode );
		$prices           = parent::prepare_product_price_response( $product, $tax_display_mode );

		// Add raw prices (prices with greater precision).
		$prices['raw_prices'] = [
			'precision'     => wc_get_rounding_precision(),
			'price'         => $this->prepare_money_response( $price_function( $product ), wc_get_rounding_precision() ),
			'regular_price' => $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_regular_price() ] ), wc_get_rounding_precision() ),
			'sale_price'    => $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_sale_price() ] ), wc_get_rounding_precision() ),
		];

		return $prices;
	}

	/**
	 * Returns the remaining stock for a product if it has stock.
	 *
	 * This also factors in draft orders.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return integer|null
	 */
	protected function get_remaining_stock( \WC_Product $product ) {
		if ( is_null( $product->get_stock_quantity() ) ) {
			return null;
		}

		$draft_order    = wc()->session->get( 'store_api_draft_order', 0 );
		$reserve_stock  = new ReserveStock();
		$reserved_stock = $reserve_stock->get_reserved_stock( $product, $draft_order );

		return $product->get_stock_quantity() - $reserved_stock;
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
				$label = wc_attribute_label( str_replace( 'attribute_', '', $key ), $product );
			}

			$return[] = [
				'attribute' => $this->prepare_html_response( $label ),
				'value'     => $this->prepare_html_response( $value ),
			];
		}

		return $return;
	}

	/**
	 * Format cart item data removing any HTML tag.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	protected function get_item_data( $cart_item ) {
		$item_data = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );
		return array_map( [ $this, 'format_item_data_element' ], $item_data );
	}

	/**
	 * Remove HTML tags from cart item data and set the `hidden` property to
	 * `__experimental_woocommerce_blocks_hidden`.
	 *
	 * @param array $item_data_element Individual element of a cart item data.
	 * @return array
	 */
	protected function format_item_data_element( $item_data_element ) {
		if ( array_key_exists( '__experimental_woocommerce_blocks_hidden', $item_data_element ) ) {
			$item_data_element['hidden'] = $item_data_element['__experimental_woocommerce_blocks_hidden'];
		}
		return array_map( 'wp_strip_all_tags', $item_data_element );
	}
}
