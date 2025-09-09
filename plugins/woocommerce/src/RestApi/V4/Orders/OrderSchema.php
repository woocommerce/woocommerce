<?php
/**
 * OrderSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\V4\AbstractSchema;
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
	public function get_item_properties() {
		$schema = array(
			'id'                   => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'parent_id'            => array(
				'description' => __( 'Parent order ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
			'number'               => array(
				'description' => __( 'Order number.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'order_key'            => array(
				'description' => __( 'Order key.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'created_via'          => array(
				'description' => __( 'Shows where the order was created.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'version'              => array(
				'description' => __( 'Version of WooCommerce which last updated the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'status'               => array(
				'description' => __( 'Order status.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => OrderStatus::PENDING,
				'enum'        => array_map( 'wc_get_order_status_slug', array_merge( array( OrderStatus::AUTO_DRAFT ), array_keys( wc_get_order_statuses() ) ) ),
				'context'     => array( 'view', 'edit' ),
			),
			'currency'             => array(
				'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => get_woocommerce_currency(),
				'enum'        => array_keys( get_woocommerce_currencies() ),
				'context'     => array( 'view', 'edit' ),
			),
			'currency_symbol'      => array(
				'description' => __( 'Currency symbol for the currency which can be used to format returned prices.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_created'         => array(
				'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_created_gmt'     => array(
				'description' => __( 'The date the order was created, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_modified'        => array(
				'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_modified_gmt'    => array(
				'description' => __( 'The date the order was last modified, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_total'       => array(
				'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_tax'         => array(
				'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'shipping_total'       => array(
				'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'shipping_tax'         => array(
				'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'cart_tax'             => array(
				'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total'                => array(
				'description' => __( 'Grand total.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_tax'            => array(
				'description' => __( 'Sum of all taxes.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'prices_include_tax'   => array(
				'description' => __( 'True the prices included tax during checkout.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'customer_id'          => array(
				'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
				'type'        => 'integer',
				'default'     => 0,
				'context'     => array( 'view', 'edit' ),
			),
			'customer_ip_address'  => array(
				'description' => __( "Customer's IP address.", 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'customer_user_agent'  => array(
				'description' => __( 'User agent of the customer.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'customer_note'        => array(
				'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'billing'              => array(
				'description' => __( 'Billing address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'country'    => array(
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'email'      => array(
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => array( 'string', 'null' ),
						'format'      => 'email',
						'context'     => array( 'view', 'edit' ),
					),
					'phone'      => array(
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
			'shipping'             => array(
				'description' => __( 'Shipping address.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'properties'  => array(
					'first_name' => array(
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'last_name'  => array(
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'company'    => array(
						'description' => __( 'Company name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'address_1'  => array(
						'description' => __( 'Address line 1', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'address_2'  => array(
						'description' => __( 'Address line 2', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'city'       => array(
						'description' => __( 'City name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'state'      => array(
						'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'postcode'   => array(
						'description' => __( 'Postal code.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'country'    => array(
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
			'payment_method'       => array(
				'description' => __( 'Payment method ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'payment_method_title' => array(
				'description' => __( 'Payment method title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'transaction_id'       => array(
				'description' => __( 'Unique transaction ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'date_paid'            => array(
				'description' => __( "The date the order was paid, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_paid_gmt'        => array(
				'description' => __( 'The date the order was paid, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_completed'       => array(
				'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_completed_gmt'   => array(
				'description' => __( 'The date the order was completed, as GMT.', 'woocommerce' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'cart_hash'            => array(
				'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'meta_data'            => array(
				'description' => __( 'Meta data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'description' => __( 'Meta ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'key'   => array(
							'description' => __( 'Meta key.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'value' => array(
							'description' => __( 'Meta value.', 'woocommerce' ),
							'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
			'line_items'           => array(
				'description' => __( 'Line items data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'               => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name'             => array(
							'description' => __( 'Product name.', 'woocommerce' ),
							'type'        => array( 'string', 'null' ),
							'context'     => array( 'view', 'edit' ),
						),
						'parent_name'      => array(
							'description' => __( 'Parent product name if the product is a variation.', 'woocommerce' ),
							'type'        => array( 'string', 'null' ),
							'context'     => array( 'view', 'edit' ),
						),
						'product_id'       => array(
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => array( 'integer', 'null' ),
							'context'     => array( 'view', 'edit' ),
						),
						'variation_id'     => array(
							'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'quantity'         => array(
							'description' => __( 'Quantity ordered.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class'        => array(
							'description' => __( 'Tax class of product.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'subtotal'         => array(
							'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'subtotal_tax'     => array(
							'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total'            => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax'        => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'taxes'            => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
									),
									'total'    => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'subtotal' => array(
										'description' => __( 'Tax subtotal.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
						'meta_data'        => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'            => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'key'           => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value'         => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
									),
									'display_key'   => array(
										'description' => __( 'Meta key for UI display.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'display_value' => array(
										'description' => __( 'Meta value for UI display.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
						'sku'              => array(
							'description' => __( 'Product SKU.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'global_unique_id' => array(
							'description' => __( 'GTIN, UPC, EAN or ISBN.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price'            => array(
							'description' => __( 'Product price.', 'woocommerce' ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'image'            => array(
							'description' => __( 'Properties of the main product image.', 'woocommerce' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id'  => array(
									'description' => __( 'Image ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
								),
								'src' => array(
									'description' => __( 'Image URL.', 'woocommerce' ),
									'type'        => 'string',
									'format'      => 'uri',
									'context'     => array( 'view', 'edit' ),
								),
							),
						),
					),
				),
			),
			'tax_lines'            => array(
				'description' => __( 'Tax lines data.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'                 => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_code'          => array(
							'description' => __( 'Tax rate code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_id'            => array(
							'description' => __( 'Tax rate ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'label'              => array(
							'description' => __( 'Tax rate label.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'compound'           => array(
							'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'tax_total'          => array(
							'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'shipping_tax_total' => array(
							'description' => __( 'Shipping tax total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'meta_data'          => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
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
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'method_title' => array(
							'description' => __( 'Shipping method name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'method_id'    => array(
							'description' => __( 'Shipping method ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'instance_id'  => array(
							'description' => __( 'Shipping instance ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total'        => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax'    => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'taxes'        => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'total' => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
						'meta_data'    => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
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
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'         => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name'       => array(
							'description' => __( 'Fee name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class'  => array(
							'description' => __( 'Tax class of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_status' => array(
							'description' => __( 'Tax status of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'enum'        => array( 'taxable', 'none' ),
						),
						'total'      => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax'  => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'taxes'      => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array(
										'description' => __( 'Tax rate ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'total'    => array(
										'description' => __( 'Tax total.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'subtotal' => array(
										'description' => __( 'Tax subtotal.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
						'meta_data'  => array(
							'description' => __( 'Meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
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
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'             => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'code'           => array(
							'description' => __( 'Coupon code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'discount'       => array(
							'description' => __( 'Discount total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'discount_tax'   => array(
							'description' => __( 'Discount total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
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
							'context'     => array( 'view', 'edit' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array(
										'description' => __( 'Meta ID.', 'woocommerce' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'key'   => array(
										'description' => __( 'Meta key.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'value' => array(
										'description' => __( 'Meta value.', 'woocommerce' ),
										'type'        => array( 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ),
										'context'     => array( 'view', 'edit' ),
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
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array(
							'description' => __( 'Refund ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'reason' => array(
							'description' => __( 'Refund reason.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total'  => array(
							'description' => __( 'Refund total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'payment_url'          => array(
				'description' => __( 'Order payment URL.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
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
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'needs_payment'        => array(
				'description' => __( 'Whether an order needs payment, based on status and order total.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'needs_processing'     => array(
				'description' => __( 'Whether an order needs processing before it can be completed.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
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
			'description' => __( 'Cost of Goods Sold data.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Total value of the Cost of Goods Sold for the order.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		$schema['line_items']['items']['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data. Only present for product line items.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'total_value' => array(
					'description' => __( 'Value of the Cost of Goods Sold for the order item.', 'woocommerce' ),
					'type'        => 'number',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}
}
