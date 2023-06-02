<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;


/**
 * ProductSchema class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
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
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product';

	/**
	 * Image attachment schema instance.
	 *
	 * @var ImageAttachmentSchema
	 */
	protected $image_attachment_schema;

	/**
	 * Constructor.
	 *
	 * @param ExtendRestApi         $extend Rest Extending instance.
	 * @param ImageAttachmentSchema $image_attachment_schema Image attachment schema instance.
	 */
	public function __construct( ExtendRestApi $extend, ImageAttachmentSchema $image_attachment_schema ) {
		$this->image_attachment_schema = $image_attachment_schema;
		parent::__construct( $extend );
	}

	/**
	 * Product schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'                  => [
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'name'                => [
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'parent'              => [
				'description' => __( 'ID of the parent product, if applicable.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'type'                => [
				'description' => __( 'Product type.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'variation'           => [
				'description' => __( 'Product variation attributes, if applicable.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'permalink'           => [
				'description' => __( 'Product URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'short_description'   => [
				'description' => __( 'Product short description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'description'         => [
				'description' => __( 'Product full description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'on_sale'             => [
				'description' => __( 'Is the product on sale?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sku'                 => [
				'description' => __( 'Unique identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'prices'              => [
				'description' => __( 'Price data provided using the smallest unit of the currency.', 'woocommerce' ),
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
					]
				),
			],
			'price_html'          => array(
				'description' => __( 'Price string formatted as HTML.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'average_rating'      => [
				'description' => __( 'Reviews average rating.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'review_count'        => [
				'description' => __( 'Amount of reviews that the product has.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'images'              => [
				'description' => __( 'List of images.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				],
			],
			'categories'          => [
				'description' => __( 'List of categories, if applicable.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'   => [
							'description' => __( 'Category ID', 'woocommerce' ),
							'type'        => 'number',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'name' => [
							'description' => __( 'Category name', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'slug' => [
							'description' => __( 'Category slug', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'link' => [
							'description' => __( 'Category link', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'tags'                => [
				'description' => __( 'List of tags, if applicable.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'   => [
							'description' => __( 'Tag ID', 'woocommerce' ),
							'type'        => 'number',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'name' => [
							'description' => __( 'Tag name', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'slug' => [
							'description' => __( 'Tag slug', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'link' => [
							'description' => __( 'Tag link', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'attributes'          => [
				'description' => __( 'List of attributes assigned to the product/variation that are visible or used for variations.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'             => [
							'description' => __( 'The attribute ID, or 0 if the attribute is not taxonomy based.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'name'           => [
							'description' => __( 'The attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'taxonomy'       => [
							'description' => __( 'The attribute taxonomy, or null if the attribute is not taxonomy based.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'has_variations' => [
							'description' => __( 'True if this attribute is used by product variations.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'terms'          => [
							'description' => __( 'List of assigned attribute terms.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => [ 'view', 'edit' ],
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'id'   => [
										'description' => __( 'The term ID, or 0 if the attribute is not a global attribute.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
									'name' => [
										'description' => __( 'The term name.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
									'slug' => [
										'description' => __( 'The term slug.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
								],
							],
						],
					],
				],
			],
			'variations'          => [
				'description' => __( 'List of variation IDs, if applicable.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'         => [
							'description' => __( 'The attribute ID, or 0 if the attribute is not taxonomy based.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'attributes' => [
							'description' => __( 'List of variation attributes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => [ 'view', 'edit' ],
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'name'  => [
										'description' => __( 'The attribute name.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
									'value' => [
										'description' => __( 'The assigned attribute.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
								],
							],
						],
					],
				],
			],
			'has_options'         => [
				'description' => __( 'Does the product have additional options before it can be added to the cart?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'is_purchasable'      => [
				'description' => __( 'Is the product purchasable?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'is_in_stock'         => [
				'description' => __( 'Is the product in stock?', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'is_on_backorder'     => [
				'description' => __( 'Is the product stock backordered? This will also return false if backorder notifications are turned off.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'low_stock_remaining' => [
				'description' => __( 'Quantity left in stock if stock is low, or null if not applicable.', 'woocommerce' ),
				'type'        => [ 'integer', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sold_individually'   => [
				'description' => __( 'If true, only one item of this product is allowed for purchase in a single order.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'quantity_limit'      => [
				'description' => __( 'The maximum quantity than can be added to the cart at once.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'add_to_cart'         => [
				'description' => __( 'Add to cart button parameters.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'text'        => [
						'description' => __( 'Button text.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'description' => [
						'description' => __( 'Button description.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'url'         => [
						'description' => __( 'Add to cart URL.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
				],
			],
		];
	}

	/**
	 * Convert a WooCommerce product into an object suitable for the response.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	public function get_item_response( $product ) {
		return [
			'id'                  => $product->get_id(),
			'name'                => $this->prepare_html_response( $product->get_title() ),
			'parent'              => $product->get_parent_id(),
			'type'                => $product->get_type(),
			'variation'           => $this->prepare_html_response( $product->is_type( 'variation' ) ? wc_get_formatted_variation( $product, true, true, false ) : '' ),
			'permalink'           => $product->get_permalink(),
			'sku'                 => $this->prepare_html_response( $product->get_sku() ),
			'short_description'   => $this->prepare_html_response( wc_format_content( $product->get_short_description() ) ),
			'description'         => $this->prepare_html_response( wc_format_content( $product->get_description() ) ),
			'on_sale'             => $product->is_on_sale(),
			'prices'              => (object) $this->prepare_product_price_response( $product ),
			'price_html'          => $product->get_price_html(),
			'average_rating'      => $product->get_average_rating(),
			'review_count'        => $product->get_review_count(),
			'images'              => $this->get_images( $product ),
			'categories'          => $this->get_term_list( $product, 'product_cat' ),
			'tags'                => $this->get_term_list( $product, 'product_tag' ),
			'attributes'          => $this->get_attributes( $product ),
			'variations'          => $this->get_variations( $product ),
			'has_options'         => $product->has_options(),
			'is_purchasable'      => $product->is_purchasable(),
			'is_in_stock'         => $product->is_in_stock(),
			'is_on_backorder'     => 'onbackorder' === $product->get_stock_status(),
			'low_stock_remaining' => $this->get_low_stock_remaining( $product ),
			'sold_individually'   => $product->is_sold_individually(),
			'quantity_limit'      => $this->get_product_quantity_limit( $product ),
			'add_to_cart'         => (object) $this->prepare_html_response(
				[
					'text'        => $product->add_to_cart_text(),
					'description' => $product->add_to_cart_description(),
					'url'         => $product->add_to_cart_url(),
				]
			),
		];
	}

	/**
	 * Get list of product images.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_images( \WC_Product $product ) {
		$attachment_ids = array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() );

		return array_filter( array_map( [ $this->image_attachment_schema, 'get_item_response' ], $attachment_ids ) );
	}

	/**
	 * Gets remaining stock amount for a product.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return integer|null
	 */
	protected function get_remaining_stock( \WC_Product $product ) {
		if ( is_null( $product->get_stock_quantity() ) ) {
			return null;
		}
		return $product->get_stock_quantity();
	}

	/**
	 * If a product has low stock, return the remaining stock amount for display.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return integer|null
	 */
	protected function get_low_stock_remaining( \WC_Product $product ) {
		$remaining_stock = $this->get_remaining_stock( $product );

		if ( ! is_null( $remaining_stock ) && $remaining_stock <= wc_get_low_stock_amount( $product ) ) {
			return max( $remaining_stock, 0 );
		}

		return null;
	}

	/**
	 * Get the quantity limit for an item in the cart.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return int
	 */
	protected function get_product_quantity_limit( \WC_Product $product ) {
		$limits = [ 99 ];

		if ( $product->is_sold_individually() ) {
			$limits[] = 1;
		} elseif ( ! $product->backorders_allowed() ) {
			$limits[] = $this->get_remaining_stock( $product );
		}

		return apply_filters( 'woocommerce_store_api_product_quantity_limit', max( min( array_filter( $limits ) ), 1 ), $product );
	}

	/**
	 * Returns true if the given attribute is valid.
	 *
	 * @param mixed $attribute Object or variable to check.
	 * @return boolean
	 */
	protected function filter_valid_attribute( $attribute ) {
		return is_a( $attribute, '\WC_Product_Attribute' );
	}

	/**
	 * Returns true if the given attribute is valid and used for variations.
	 *
	 * @param mixed $attribute Object or variable to check.
	 * @return boolean
	 */
	protected function filter_variation_attribute( $attribute ) {
		return $this->filter_valid_attribute( $attribute ) && $attribute->get_variation();
	}

	/**
	 * Get variation IDs and attributes from the DB.
	 *
	 * @param \WC_Product $product Product instance.
	 * @returns array
	 */
	protected function get_variations( \WC_Product $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return [];
		}
		global $wpdb;

		$variation_ids               = $product->get_visible_children();
		$attributes                  = array_filter( $product->get_attributes(), [ $this, 'filter_variation_attribute' ] );
		$default_variation_meta_data = array_reduce(
			$attributes,
			function( $defaults, $attribute ) use ( $product ) {
				$meta_key              = wc_variation_attribute_name( $attribute->get_name() );
				$defaults[ $meta_key ] = [
					'name'  => wc_attribute_label( $attribute->get_name(), $product ),
					'value' => null,
				];
				return $defaults;
			},
			[]
		);

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$variation_meta_data = $wpdb->get_results(
			"
			SELECT post_id as variation_id, meta_key as attribute_key, meta_value as attribute_value
			FROM {$wpdb->postmeta}
			WHERE post_id IN (" . implode( ',', array_map( 'esc_sql', $variation_ids ) ) . ")
			AND meta_key IN ('" . implode( "','", array_map( 'esc_sql', array_keys( $default_variation_meta_data ) ) ) . "')
		"
		);
		// phpcs:enable

		$attributes_by_variation = array_reduce(
			$variation_meta_data,
			function( $values, $data ) {
				$values[ $data->variation_id ][ $data->attribute_key ] = $data->attribute_value;
				return $values;
			},
			array_fill_keys( $variation_ids, [] )
		);

		$variations = [];

		foreach ( $variation_ids as $variation_id ) {
			$attribute_data = $default_variation_meta_data;

			foreach ( $attributes_by_variation[ $variation_id ] as $meta_key => $meta_value ) {
				if ( '' !== $meta_value ) {
					$attribute_data[ $meta_key ]['value'] = $meta_value;
				}
			}

			$variations[] = (object) [
				'id'         => $variation_id,
				'attributes' => array_values( $attribute_data ),
			];
		}

		return $variations;
	}

	/**
	 * Get list of product attributes and attribute terms.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_attributes( \WC_Product $product ) {
		$attributes = array_filter( $product->get_attributes(), [ $this, 'filter_valid_attribute' ] );
		$return     = [];

		foreach ( $attributes as $attribute_slug => $attribute ) {
			// Only visible and variation attributes will be exposed by this API.
			if ( ! $attribute->get_visible() || ! $attribute->get_variation() ) {
				continue;
			}
			$return[] = (object) [
				'id'             => $attribute->get_id(),
				'name'           => wc_attribute_label( $attribute->get_name(), $product ),
				'taxonomy'       => $attribute->is_taxonomy() ? $attribute->get_name() : null,
				'has_variations' => true === $attribute->get_variation(),
				'terms'          => $attribute->is_taxonomy() ? array_map( [ $this, 'prepare_product_attribute_taxonomy_value' ], $attribute->get_terms() ) : array_map( [ $this, 'prepare_product_attribute_value' ], $attribute->get_options() ),
			];
		}

		return $return;
	}

	/**
	 * Prepare an attribute term for the response.
	 *
	 * @param \WP_Term $term Term object.
	 * @return object
	 */
	protected function prepare_product_attribute_taxonomy_value( \WP_Term $term ) {
		return $this->prepare_product_attribute_value( $term->name, $term->term_id, $term->slug );
	}

	/**
	 * Prepare an attribute term for the response.
	 *
	 * @param string $name Attribute term name.
	 * @param int    $id Attribute term ID.
	 * @param string $slug Attribute term slug.
	 * @return object
	 */
	protected function prepare_product_attribute_value( $name, $id = 0, $slug = '' ) {
		return (object) [
			'id'   => (int) $id,
			'name' => $name,
			'slug' => $slug ? $slug : $name,
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
		$prices           = [];
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );
		$price_function   = $this->get_price_function_from_tax_display_mode( $tax_display_mode );

		$prices['price']         = $this->prepare_money_response( $price_function( $product ), wc_get_price_decimals() );
		$prices['regular_price'] = $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_regular_price() ] ), wc_get_price_decimals() );
		$prices['sale_price']    = $this->prepare_money_response( $price_function( $product, [ 'price' => $product->get_sale_price() ] ), wc_get_price_decimals() );
		$prices['price_range']   = $this->get_price_range( $product, $tax_display_mode );

		return $this->prepare_currency_response( $prices );
	}

	/**
	 * WooCommerce can return prices including or excluding tax; choose the correct method based on tax display mode.
	 *
	 * @param string $tax_display_mode Provided tax display mode.
	 * @return string Valid tax display mode.
	 */
	protected function get_tax_display_mode( $tax_display_mode = '' ) {
		return in_array( $tax_display_mode, [ 'incl', 'excl' ], true ) ? $tax_display_mode : get_option( 'woocommerce_tax_display_shop' );
	}

	/**
	 * WooCommerce can return prices including or excluding tax; choose the correct method based on tax display mode.
	 *
	 * @param string $tax_display_mode If returned prices are incl or excl of tax.
	 * @return string Function name.
	 */
	protected function get_price_function_from_tax_display_mode( $tax_display_mode ) {
		return 'incl' === $tax_display_mode ? 'wc_get_price_including_tax' : 'wc_get_price_excluding_tax';
	}

	/**
	 * Get price range from certain product types.
	 *
	 * @param \WC_Product $product Product instance.
	 * @param string      $tax_display_mode If returned prices are incl or excl of tax.
	 * @return object|null
	 */
	protected function get_price_range( \WC_Product $product, $tax_display_mode = '' ) {
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );

		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );

			if ( ! empty( $prices['price'] ) && ( min( $prices['price'] ) !== max( $prices['price'] ) ) ) {
				return (object) [
					'min_amount' => $this->prepare_money_response( min( $prices['price'] ), wc_get_price_decimals() ),
					'max_amount' => $this->prepare_money_response( max( $prices['price'] ), wc_get_price_decimals() ),
				];
			}
		}

		if ( $product->is_type( 'grouped' ) ) {
			$children       = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );
			$price_function = 'incl' === $tax_display_mode ? 'wc_get_price_including_tax' : 'wc_get_price_excluding_tax';

			foreach ( $children as $child ) {
				if ( '' !== $child->get_price() ) {
					$child_prices[] = $price_function( $child );
				}
			}

			if ( ! empty( $child_prices ) ) {
				return (object) [
					'min_amount' => $this->prepare_money_response( min( $child_prices ), wc_get_price_decimals() ),
					'max_amount' => $this->prepare_money_response( max( $child_prices ), wc_get_price_decimals() ),
				];
			}
		}

		return null;
	}

	/**
	 * Returns a list of terms assigned to the product.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $taxonomy Taxonomy name.
	 * @return array Array of terms (id, name, slug).
	 */
	protected function get_term_list( \WC_Product $product, $taxonomy = '' ) {
		if ( ! $taxonomy ) {
			return [];
		}

		$terms = get_the_terms( $product->get_id(), $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return [];
		}

		$return           = [];
		$default_category = (int) get_option( 'default_product_cat', 0 );

		foreach ( $terms as $term ) {
			$link = get_term_link( $term, $taxonomy );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			if ( $term->term_id === $default_category ) {
				continue;
			}

			$return[] = (object) [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'link' => $link,
			];
		}

		return $return;
	}
}
