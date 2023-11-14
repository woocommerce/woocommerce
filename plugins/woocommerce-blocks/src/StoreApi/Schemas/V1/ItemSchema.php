<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ItemSchema class.
 */
abstract class ItemSchema extends ProductSchema {

	/**
	 * Item schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'key'                  => [
				'description' => __( 'Unique identifier for the item.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'type'                 => [
				'description' => __( 'The item type.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'id'                   => [
				'description' => __( 'The item product or variation ID.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'quantity'             => [
				'description' => __( 'Quantity of this item.', 'woo-gutenberg-products-block' ),
				'type'        => 'number',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'quantity_limits'      => [
				'description' => __( 'How the quantity of this item should be controlled, for example, any limits in place.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'minimum'     => [
						'description' => __( 'The minimum quantity allowed for this line item.', 'woo-gutenberg-products-block' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'maximum'     => [
						'description' => __( 'The maximum quantity allowed for this line item.', 'woo-gutenberg-products-block' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'multiple_of' => [
						'description' => __( 'The amount that quantities increment by. Quantity must be an multiple of this value.', 'woo-gutenberg-products-block' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
						'default'     => 1,
					],
					'editable'    => [
						'description' => __( 'If the quantity is editable or fixed.', 'woo-gutenberg-products-block' ),
						'type'        => 'boolean',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
						'default'     => true,
					],
				],
			],
			'name'                 => [
				'description' => __( 'Product name.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'short_description'    => [
				'description' => __( 'Product short description in HTML format.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'description'          => [
				'description' => __( 'Product full description in HTML format.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sku'                  => [
				'description' => __( 'Stock keeping unit, if applicable.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'low_stock_remaining'  => [
				'description' => __( 'Quantity left in stock if stock is low, or null if not applicable.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'integer', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'backorders_allowed'   => [
				'description' => __( 'True if backorders are allowed past stock availability.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'boolean' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'show_backorder_badge' => [
				'description' => __( 'True if the product is on backorder.', 'woo-gutenberg-products-block' ),
				'type'        => [ 'boolean' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'sold_individually'    => [
				'description' => __( 'If true, only one item of this product is allowed for purchase in a single order.', 'woo-gutenberg-products-block' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'permalink'            => [
				'description' => __( 'Product URL.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'images'               => [
				'description' => __( 'List of images.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				],
			],
			'variation'            => [
				'description' => __( 'Chosen attributes (for variations).', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'attribute' => [
							'description' => __( 'Variation attribute name.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'value'     => [
							'description' => __( 'Variation attribute value.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'item_data'            => [
				'description' => __( 'Metadata related to the item', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'name'    => [
							'description' => __( 'Name of the metadata.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'value'   => [
							'description' => __( 'Value of the metadata.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'display' => [
							'description' => __( 'Optionally, how the metadata value should be displayed to the user.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'prices'               => [
				'description' => __( 'Price data for the product in the current line item, including or excluding taxes based on the "display prices during cart and checkout" setting. Provided using the smallest unit of the currency.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'price'         => [
							'description' => __( 'Current product price.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'regular_price' => [
							'description' => __( 'Regular product price.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'sale_price'    => [
							'description' => __( 'Sale product price, if applicable.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'price_range'   => [
							'description' => __( 'Price range, if applicable.', 'woo-gutenberg-products-block' ),
							'type'        => [ 'object', 'null' ],
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'properties'  => [
								'min_amount' => [
									'description' => __( 'Price amount.', 'woo-gutenberg-products-block' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'max_amount' => [
									'description' => __( 'Price amount.', 'woo-gutenberg-products-block' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
							],
						],
						'raw_prices'    => [
							'description' => __( 'Raw unrounded product prices used in calculations. Provided using a higher unit of precision than the currency.', 'woo-gutenberg-products-block' ),
							'type'        => [ 'object', 'null' ],
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'properties'  => [
								'precision'     => [
									'description' => __( 'Decimal precision of the returned prices.', 'woo-gutenberg-products-block' ),
									'type'        => 'integer',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'price'         => [
									'description' => __( 'Current product price.', 'woo-gutenberg-products-block' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'regular_price' => [
									'description' => __( 'Regular product price.', 'woo-gutenberg-products-block' ),
									'type'        => 'string',
									'context'     => [ 'view', 'edit' ],
									'readonly'    => true,
								],
								'sale_price'    => [
									'description' => __( 'Sale product price, if applicable.', 'woo-gutenberg-products-block' ),
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
			'catalog_visibility'   => [
				'description' => __( 'Whether the product is visible in the catalog', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			self::EXTENDING_KEY    => $this->get_extended_schema( self::IDENTIFIER ),
		];
	}
}
