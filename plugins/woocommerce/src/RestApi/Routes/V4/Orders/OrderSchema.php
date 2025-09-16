<?php
/**
 * OrderSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;

/**
 * OrderSchema class.
 */
class OrderSchema extends AbstractSchema {
	use CogsAwareTrait;

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order';

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public static function get_item_schema_properties(): array {
		$schema = array(
			'id'                   => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'parent_id'            => array(
				'description' => __( 'Parent order ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'number'               => array(
				'description' => __( 'Order number.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'order_key'            => array(
				'description' => __( 'Order key.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'created_via'          => array(
				'description' => __( 'Shows where the order was created.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'version'              => array(
				'description' => __( 'Version of WooCommerce which last updated the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'status'               => array(
				'description' => __( 'Order status.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => OrderStatus::PENDING,
				'enum'        => array_map( 'wc_get_order_status_slug', array_merge( array( OrderStatus::AUTO_DRAFT ), array_keys( wc_get_order_statuses() ) ) ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'currency'             => array(
				'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => get_woocommerce_currency(),
				'enum'        => array_keys( get_woocommerce_currencies() ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'currency_symbol'      => array(
				'description' => __( 'Currency symbol for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_created'         => array(
				'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_created_gmt'     => array(
				'description' => __( 'The date the order was created, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_modified'        => array(
				'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_modified_gmt'    => array(
				'description' => __( 'The date the order was last modified, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'discount_total'       => array(
				'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'discount_tax'         => array(
				'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'shipping_total'       => array(
				'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'shipping_tax'         => array(
				'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'cart_tax'             => array(
				'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'total'                => array(
				'description' => __( 'Grand total.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'total_tax'            => array(
				'description' => __( 'Sum of all taxes.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'prices_include_tax'   => array(
				'description' => __( 'True the prices included tax during checkout.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'customer_id'          => array(
				'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
				'type'        => 'integer',
				'default'     => 0,
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'customer_ip_address'  => array(
				'description' => __( "Customer's IP address.", 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'customer_user_agent'  => array(
				'description' => __( 'User agent of the customer.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'customer_note'        => array(
				'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'billing'              => array(
				'description' => __( 'Billing address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'country'    => array(
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'email'      => array(
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => array( 'string', 'null' ),
						'format'      => 'email',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'phone'      => array(
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
				),
			),
			'shipping'             => array(
				'description' => __( 'Shipping address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
					'country'    => array(
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
					),
				),
			),
			'payment_method'       => array(
				'description' => __( 'Payment method ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'payment_method_title' => array(
				'description' => __( 'Payment method title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'transaction_id'       => array(
				'description' => __( 'Unique transaction ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
			'date_paid'            => array(
				'description' => __( "The date the order was paid, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_paid_gmt'        => array(
				'description' => __( 'The date the order was paid, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_completed'       => array(
				'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_completed_gmt'   => array(
				'description' => __( 'The date the order was completed, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'cart_hash'            => array(
				'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'meta_data'            => array(
				'description' => __( 'Meta data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'description' => __( 'Meta ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'key'   => array(
							'description' => __( 'Meta key.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'value' => array(
							'description' => __( 'Meta value.', 'woocommerce' ),
							'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
					),
				),
			),
			'line_items'           => array(
				'description' => __( 'Line items data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'               => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'name'             => array(
							'description' => __( 'Product name.', 'woocommerce' ),
							'type'        => array( 'string', 'null' ),
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'parent_name'      => array(
							'description' => __( 'Parent product name if the product is a variation.', 'woocommerce' ),
							'type'        => array( 'string', 'null' ),
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'product_id'       => array(
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => array( 'integer', 'null' ),
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'variation_id'     => array(
							'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'quantity'         => array(
							'description' => __( 'Quantity ordered.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'tax_class'        => array(
							'description' => __( 'Tax class of product.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'subtotal'         => array(
							'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'subtotal_tax'     => array(
							'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'total'            => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'total_tax'        => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'taxes'            => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'total'    => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'subtotal' => array(
										'description' => __( 'Tax subtotal.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
						'meta_data'        => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'            => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'key'           => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'value'         => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'display_key'   => array(
										'description' => __( 'Meta key for UI display.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'display_value' => array(
										'description' => __( 'Meta value for UI display.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
						'sku'              => array(
							'description' => __( 'Product SKU.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'global_unique_id' => array(
							'description' => __( 'GTIN, UPC, EAN or ISBN.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'price'            => array(
							'description' => __( 'Product price.', 'woocommerce' ),
							'type'        => 'number',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'image'            => array(
							'description' => __( 'Properties of the main product image.', 'woocommerce' ),
							'type'        => 'object',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
							'properties'  => array(
								'id'  => array(
									'description' => __( 'Image ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
								),
								'src' => array(
									'description' => __( 'Image URL.', 'woocommerce' ),
									'type'        => 'string',
									'format'      => 'uri',
									'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
								),
							),
						),
						'product_type'     => array(
							'description' => __( 'Product type.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'is_virtual'       => array(
							'description' => __( 'Is virtual product.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'is_downloadable'  => array(
							'description' => __( 'Is downloadable product.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'needs_shipping'   => array(
							'description' => __( 'Needs shipping.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'permalink'        => array(
							'description' => __( 'Product permalink.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
					),
				),
			),
			'tax_lines'            => array(
				'description' => __( 'Tax lines data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'                 => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'rate_code'          => array(
							'description' => __( 'Tax rate code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'rate_id'            => array(
							'description' => __( 'Tax rate ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'label'              => array(
							'description' => __( 'Tax rate label.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'compound'           => array(
							'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'tax_total'          => array(
							'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'shipping_tax_total' => array(
							'description' => __( 'Shipping tax total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'meta_data'          => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
					),
				),
			),
			'shipping_lines'       => array(
				'description' => __( 'Shipping lines data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'method_title' => array(
							'description' => __( 'Shipping method name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'method_id'    => array(
							'description' => __( 'Shipping method ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'instance_id'  => array(
							'description' => __( 'Shipping instance ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
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
						'taxes'        => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'total' => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
								),
							),
						),
						'meta_data'    => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
					),
				),
			),
			'fee_lines'            => array(
				'description' => __( 'Fee lines data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'name'       => array(
							'description' => __( 'Fee name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'tax_class'  => array(
							'description' => __( 'Tax class of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'tax_status' => array(
							'description' => __( 'Tax status of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'enum'        => array( 'taxable', 'none' ),
						),
						'total'      => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
						),
						'total_tax'  => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'taxes'      => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'total'    => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'subtotal' => array(
										'description' => __( 'Tax subtotal.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
								),
							),
						),
						'meta_data'  => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
					),
				),
			),
			'coupon_lines'         => array(
				'description' => __( 'Coupons line data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'             => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'code'           => array(
							'description' => __( 'Coupon code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'discount'       => array(
							'description' => __( 'Discount total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'discount_tax'   => array(
							'description' => __( 'Discount total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'discount_type'  => array(
							'description' => __( 'Discount type.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'nominal_amount' => array(
							'description' => __( 'Discount amount as defined in the coupon (absolute value or a percent, depending on the discount type).', 'woocommerce' ),
							'type'        => 'number',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'free_shipping'  => array(
							'description' => __( 'Whether the coupon grants free shipping or not.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'meta_data'      => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
									),
								),
							),
						),
					),
				),
			),
			'refunds'              => array(
				'description' => __( 'List of refunds.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array(
							'description' => __( 'Refund ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'reason' => array(
							'description' => __( 'Refund reason.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
						'total'  => array(
							'description' => __( 'Refund total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
							'readonly'    => true,
						),
					),
				),
			),
			'payment_url'          => array(
				'description' => __( 'Order payment URL.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'set_paid'             => array(
				'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => array( 'edit' ),
			),
			'is_editable'          => array(
				'description' => __( 'Whether an order can be edited.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'needs_payment'        => array(
				'description' => __( 'Whether an order needs payment, based on status and order total.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'needs_processing'     => array(
				'description' => __( 'Whether an order needs processing before it can be completed.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
		);

		if ( self::cogs_is_enabled() ) {
			$schema = self::add_cogs_related_schema( $schema );
		}

		return $schema;
	}

	/**
	 * Add the Cost of Goods Sold related fields to the schema.
	 *
	 * @param array $schema The original schema.
	 * @return array The updated schema.
	 */
	private static function add_cogs_related_schema( array $schema ): array {
		$schema['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Total value of the Cost of Goods Sold for the order.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				),
			),
		);

		$schema['line_items']['items']['properties']['cost_of_goods_sold'] = array(
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
}
