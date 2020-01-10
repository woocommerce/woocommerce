<?php
/**
 * Order schema for the Store API.
 *
 * Note, only fields customers can edit via checkout are editable. Everything else is either readonly or hidden.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * OrderSchema class.
 */
class OrderSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'order';

	/**
	 * Order schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'id'                 => [
				'description' => __( 'Unique identifier for the resource.', 'woo-gutenberg-products-block' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'number'             => [
				'description' => __( 'Generated order number which may differ from the Order ID.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'status'             => [
				'description' => __( 'Order status. Payment providers will update this value after payment.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'order_key'          => [
				'description' => __( 'Order key used to check validity or protect access to certain order data.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'created_via'        => [
				'description' => __( 'Shows where the order was created.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'prices_include_tax' => [
				'description' => __( 'True if the prices included tax when the order was created.', 'woo-gutenberg-products-block' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'events'             => [
				'description' => __( 'List of events and dates such as creation date.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'date_created'       => [
						'description' => __( "The date the order was created, in the site's timezone.", 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_created_gmt'   => [
						'description' => __( 'The date the order was created, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_modified'      => [
						'description' => __( "The date the order was last modified, in the site's timezone.", 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_modified_gmt'  => [
						'description' => __( 'The date the order was last modified, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_paid'          => [
						'description' => __( "The date the order was paid, in the site's timezone.", 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_paid_gmt'      => [
						'description' => __( 'The date the order was paid, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_completed'     => [
						'description' => __( "The date the order was completed, in the site's timezone.", 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_completed_gmt' => [
						'description' => __( 'The date the order was completed, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => 'date-time',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
				],
			],
			'customer'           => [
				'description' => __( 'Information about the customer that placed the order.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'customer_id'         => [
						'description' => __( 'Customer ID if registered. Will return 0 for guest orders.', 'woo-gutenberg-products-block' ),
						'type'        => 'integer',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'customer_ip_address' => [
						'description' => __( 'Customer IP address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'customer_user_agent' => [
						'description' => __( 'Customer web browser identifier.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
				],
			],
			'customer_note'      => [
				'description' => __( 'Note added to the order by the customer during checkout.', 'woo-gutenberg-products-block' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'billing_address'    => [
				'description' => __( 'Billing address.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'first_name' => [
						'description' => __( 'First name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'last_name'  => [
						'description' => __( 'Last name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'company'    => [
						'description' => __( 'Company name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_1'  => [
						'description' => __( 'Address line 1', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_2'  => [
						'description' => __( 'Address line 2', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'city'       => [
						'description' => __( 'City name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'state'      => [
						'description' => __( 'ISO code or name of the state, province or district.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'postcode'   => [
						'description' => __( 'Postal code.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'country'    => [
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'email'      => [
						'description' => __( 'Email address.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'format'      => 'email',
						'context'     => [ 'view', 'edit' ],
					],
					'phone'      => [
						'description' => __( 'Phone number.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
				],
			],
			'shipping_address'   => [
				'description' => __( 'Shipping address.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'first_name' => [
						'description' => __( 'First name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'last_name'  => [
						'description' => __( 'Last name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'company'    => [
						'description' => __( 'Company name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_1'  => [
						'description' => __( 'Address line 1', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'address_2'  => [
						'description' => __( 'Address line 2', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'city'       => [
						'description' => __( 'City name.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'state'      => [
						'description' => __( 'ISO code or name of the state, province or district.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'postcode'   => [
						'description' => __( 'Postal code.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
					'country'    => [
						'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
					],
				],
			],
			'coupons'            => [
				'description' => __( 'List of applied cart coupons.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( ( new CartCouponSchema() )->get_properties() ),
				],
			],
			'items'              => [
				'description' => __( 'List of cart items.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( ( new OrderItemSchema() )->get_properties() ),
				],
			],
			'shipping_lines'     => [
				'description' => __( 'Shipping lines data.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'           => [
							'description' => __( 'Item ID.', 'woo-gutenberg-products-block' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'method_title' => [
							'description' => __( 'Shipping method name.', 'woo-gutenberg-products-block' ),
							'type'        => 'mixed',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'method_id'    => [
							'description' => __( 'Shipping method ID.', 'woo-gutenberg-products-block' ),
							'type'        => 'mixed',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'instance_id'  => [
							'description' => __( 'Shipping instance ID.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total'        => [
							'description' => __( 'Line total (after discounts).', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_tax'    => [
							'description' => __( 'Line total tax (after discounts).', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'taxes'        => [
							'description' => __( 'Line taxes.', 'woo-gutenberg-products-block' ),
							'type'        => 'array',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'id'    => [
										'description' => __( 'Tax rate ID.', 'woo-gutenberg-products-block' ),
										'type'        => 'integer',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
									'total' => [
										'description' => __( 'Tax total.', 'woo-gutenberg-products-block' ),
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
			'totals'             => [
				'description' => __( 'Total amounts provided using the smallest unit of the currency.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'total_items'        => [
							'description' => __( 'Total price of items in the order.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_items_tax'    => [
							'description' => __( 'Total tax on items in the order.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_fees'         => [
							'description' => __( 'Total price of any applied fees.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_fees_tax'     => [
							'description' => __( 'Total tax on fees.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_discount'     => [
							'description' => __( 'Total discount from applied coupons.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_discount_tax' => [
							'description' => __( 'Total tax removed due to discount from applied coupons.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_shipping'     => [
							'description' => __( 'Total price of shipping.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_shipping_tax' => [
							'description' => __( 'Total tax on shipping.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_price'        => [
							'description' => __( 'Total price the customer will pay.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'total_tax'          => [
							'description' => __( 'Total tax applied to items and shipping.', 'woo-gutenberg-products-block' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'tax_lines'          => [
							'description' => __( 'Lines of taxes applied to items and shipping.', 'woo-gutenberg-products-block' ),
							'type'        => 'array',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'name'  => [
										'description' => __( 'The name of the tax.', 'woo-gutenberg-products-block' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
									'price' => [
										'description' => __( 'The amount of tax charged.', 'woo-gutenberg-products-block' ),
										'type'        => 'string',
										'context'     => [ 'view', 'edit' ],
										'readonly'    => true,
									],
								],
							],
						],
					]
				),
			],
		];
	}

	/**
	 * Convert a woo order into an object suitable for the response.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return array
	 */
	public function get_item_response( \WC_Order $order ) {
		$order_item_schema = new OrderItemSchema();

		return [
			'id'                 => $order->get_id(),
			'number'             => $order->get_order_number(),
			'status'             => $order->get_status(),
			'order_key'          => $order->get_order_key(),
			'created_via'        => $order->get_created_via(),
			'prices_include_tax' => $order->get_prices_include_tax(),
			'events'             => $this->get_events( $order ),
			'customer'           => [
				'customer_id'         => $order->get_customer_id(),
				'customer_ip_address' => $order->get_customer_ip_address(),
				'customer_user_agent' => $order->get_customer_user_agent(),
			],
			'customer_note'      => $order->get_customer_note(),
			'billing_address'    => [
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address_1'  => $order->get_billing_address_1(),
				'address_2'  => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			],
			'shipping_address'   => [
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'company'    => $order->get_shipping_company(),
				'address_1'  => $order->get_shipping_address_1(),
				'address_2'  => $order->get_shipping_address_2(),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			],
			'items'              => array_values( array_map( [ $order_item_schema, 'get_item_response' ], $order->get_items( 'line_item' ) ) ),
			'totals'             => array_merge(
				$this->get_store_currency_response(),
				[
					'total_items'        => $this->prepare_money_response( $order->get_subtotal(), wc_get_price_decimals() ),
					'total_items_tax'    => $this->prepare_money_response( $this->get_subtotal_tax( $order ), wc_get_price_decimals() ),
					'total_fees'         => $this->prepare_money_response( $this->get_fee_total( $order ), wc_get_price_decimals() ),
					'total_fees_tax'     => $this->prepare_money_response( $this->get_fee_tax( $order ), wc_get_price_decimals() ),
					'total_discount'     => $this->prepare_money_response( $order->get_discount_total(), wc_get_price_decimals() ),
					'total_discount_tax' => $this->prepare_money_response( $order->get_discount_tax(), wc_get_price_decimals() ),
					'total_shipping'     => $this->prepare_money_response( $order->get_shipping_total(), wc_get_price_decimals() ),
					'total_shipping_tax' => $this->prepare_money_response( $order->get_shipping_tax(), wc_get_price_decimals() ),
					'total_price'        => $this->prepare_money_response( $order->get_total(), wc_get_price_decimals() ),
					'total_tax'          => $this->prepare_money_response( $order->get_total_tax(), wc_get_price_decimals() ),
					'tax_lines'          => $this->get_tax_lines( $order ),
				]
			),
		];
	}

	/**
	 * Removes the wc- prefix from order statuses.
	 *
	 * @param string $status Status from the order.
	 * @return string
	 */
	protected function remove_status_prefix( $status ) {
		return 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
	}

	/**
	 * Get event dates from an order, formatting both local and GMT values.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return array
	 */
	protected function get_events( \WC_Order $order ) {
		$events = [];
		$props  = [ 'date_created', 'date_modified', 'date_completed', 'date_paid' ];

		foreach ( $props as $prop ) {
			$datetime                 = $order->{"get_$prop"}();
			$events[ $prop ]          = wc_rest_prepare_date_response( $datetime, false );
			$events[ $prop . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		return $events;
	}

	/**
	 * Get tax lines from the order and format to match schema.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return array
	 */
	protected function get_tax_lines( \WC_Order $order ) {
		$tax_totals = $order->get_tax_totals();
		$tax_lines  = [];

		foreach ( $tax_totals as $tax_total ) {
			$tax_lines[] = array(
				'name'  => $tax_total->label,
				'price' => $this->prepare_money_response( $tax_total->amount, wc_get_price_decimals() ),
			);
		}

		return $tax_lines;
	}

	/**
	 * Get the total amount of tax for line items.
	 *
	 * Needed because orders do not hold this total like carts.
	 *
	 * @todo In the future this could be added to the core WC_Order class to better match the WC_Cart class.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_subtotal_tax( \WC_Order $order ) {
		$total = 0;
		foreach ( $order->get_items() as $item ) {
			$total += $item->get_subtotal_tax();
		}
		return $total;
	}
	/**
	 * Get the total amount of fees.
	 *
	 * Needed because orders do not hold this total like carts.
	 *
	 * @todo In the future this could be added to the core WC_Order class to better match the WC_Cart class.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_fee_total( \WC_Order $order ) {
		$total = 0;
		foreach ( $order->get_fees() as $item ) {
			$total += $item->get_total();
		}
		return $total;
	}

	/**
	 * Get the total tax of fees.
	 *
	 * Needed because orders do not hold this total like carts.
	 *
	 * @todo In the future this could be added to the core WC_Order class to better match the WC_Cart class.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_fee_tax( \WC_Order $order ) {
		$total = 0;
		foreach ( $order->get_fees() as $item ) {
			$total += $item->get_total_tax();
		}
		return $total;
	}
}
