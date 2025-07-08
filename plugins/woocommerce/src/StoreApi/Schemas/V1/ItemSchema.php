<?php
declare( strict_types=1 );

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
		return array(
			'key'                  => array(
				'description' => __( 'Unique identifier for the item.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'type'                 => array(
				'description' => __( 'The item type.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'id'                   => array(
				'description' => __( 'The item product or variation ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity'             => array(
				'description' => __( 'Quantity of this item.', 'woocommerce' ),
				'type'        => 'number',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity_limits'      => array(
				'description' => __( 'How the quantity of this item should be controlled, for example, any limits in place.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'minimum'     => array(
						'description' => __( 'The minimum quantity allowed for this line item.', 'woocommerce' ),
						'type'        => 'number',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'maximum'     => array(
						'description' => __( 'The maximum quantity allowed for this line item.', 'woocommerce' ),
						'type'        => 'number',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'multiple_of' => array(
						'description' => __( 'The amount that quantities increment by. Quantity must be an multiple of this value.', 'woocommerce' ),
						'type'        => 'number',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'default'     => 1,
					),
					'editable'    => array(
						'description' => __( 'If the quantity is editable or fixed.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'default'     => true,
					),
				),
			),
			'name'                 => array(
				'description' => __( 'Product name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'short_description'    => array(
				'description' => __( 'Product short description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description'          => array(
				'description' => __( 'Product full description in HTML format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sku'                  => array(
				'description' => __( 'Stock keeping unit, if applicable.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'low_stock_remaining'  => array(
				'description' => __( 'Quantity left in stock if stock is low, or null if not applicable.', 'woocommerce' ),
				'type'        => array( 'number', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'backorders_allowed'   => array(
				'description' => __( 'True if backorders are allowed past stock availability.', 'woocommerce' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'show_backorder_badge' => array(
				'description' => __( 'True if the product is on backorder.', 'woocommerce' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sold_individually'    => array(
				'description' => __( 'If true, only one item of this product is allowed for purchase in a single order.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'permalink'            => array(
				'description' => __( 'Product URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'               => array(
				'description' => __( 'List of images.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				),
			),
			'variation'            => array(
				'description' => __( 'Chosen attributes (for variations).', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'raw_attribute' => array(
							'description' => __( 'Variation system generated attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'attribute'     => array(
							'description' => __( 'Variation attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'value'         => array(
							'description' => __( 'Variation attribute value.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'item_data'            => array(
				'description' => __( 'Metadata related to the item', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'    => array(
							'description' => __( 'Name of the metadata.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'value'   => array(
							'description' => __( 'Value of the metadata.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'display' => array(
							'description' => __( 'Optionally, how the metadata value should be displayed to the user.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'prices'               => array(
				'description' => __( 'Price data for the product in the current line item, including or excluding taxes based on the "display prices during cart and checkout" setting. Provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					array(
						'price'         => array(
							'description' => __( 'Current product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'regular_price' => array(
							'description' => __( 'Regular product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'sale_price'    => array(
							'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price_range'   => array(
							'description' => __( 'Price range, if applicable.', 'woocommerce' ),
							'type'        => array( 'object', 'null' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
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
						'raw_prices'    => array(
							'description' => __( 'Raw unrounded product prices used in calculations. Provided using a higher unit of precision than the currency.', 'woocommerce' ),
							'type'        => array( 'object', 'null' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'precision'     => array(
									'description' => __( 'Decimal precision of the returned prices.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'price'         => array(
									'description' => __( 'Current product price.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'regular_price' => array(
									'description' => __( 'Regular product price.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'sale_price'    => array(
									'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					)
				),
			),
			'totals'               => array(
				'description' => __( 'Item total amounts provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					array(
						'line_subtotal'     => array(
							'description' => __( 'Line subtotal (the price of the product before coupon discounts have been applied).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'line_subtotal_tax' => array(
							'description' => __( 'Line subtotal tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'line_total'        => array(
							'description' => __( 'Line total (the price of the product after coupon discounts have been applied).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'line_total_tax'    => array(
							'description' => __( 'Line total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					)
				),
			),
			'catalog_visibility'   => array(
				'description' => __( 'Whether the product is visible in the catalog', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			self::EXTENDING_KEY    => $this->get_extended_schema( self::IDENTIFIER ),
		);
	}
}
