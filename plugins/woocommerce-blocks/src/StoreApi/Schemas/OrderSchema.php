<?php
/**
 * Order schema for the Store API.
 *
 * Note, only fields customers can edit via checkout are editable. Everything else is either readonly or hidden.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

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
	 * Item schema instance.
	 *
	 * @var OrderItemSchema
	 */
	public $item_schema;

	/**
	 * Coupon schema instance.
	 *
	 * @var OrderCouponSchema
	 */
	public $coupon_schema;

	/**
	 * Billing address schema instance.
	 *
	 * @var BillingAddressSchema
	 */
	public $billing_address_schema;

	/**
	 * Shipping address schema instance.
	 *
	 * @var ShippingAddressSchema
	 */
	public $shipping_address_schema;

	/**
	 * Constructor.
	 *
	 * @param OrderItemSchema       $item_schema Item schema instance.
	 * @param OrderCouponSchema     $coupon_schema Coupon schema instance.
	 * @param BillingAddressSchema  $billing_address_schema Billing address schema instance.
	 * @param ShippingAddressSchema $shipping_address_schema Shipping address schema instance.
	 */
	public function __construct(
		OrderItemSchema $item_schema,
		OrderCouponSchema $coupon_schema,
		BillingAddressSchema $billing_address_schema,
		ShippingAddressSchema $shipping_address_schema
	) {
		$this->item_schema             = $item_schema;
		$this->coupon_schema           = $coupon_schema;
		$this->billing_address_schema  = $billing_address_schema;
		$this->shipping_address_schema = $shipping_address_schema;
	}

	/**
	 * Order schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
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
						'type'        => [ 'date-time', 'null' ],
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_paid_gmt'      => [
						'description' => __( 'The date the order was paid, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => [ 'date-time', 'null' ],
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_completed'     => [
						'description' => __( "The date the order was completed, in the site's timezone.", 'woo-gutenberg-products-block' ),
						'type'        => [ 'date-time', 'null' ],
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'date_completed_gmt' => [
						'description' => __( 'The date the order was completed, as GMT.', 'woo-gutenberg-products-block' ),
						'type'        => [ 'date-time', 'null' ],
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
						'description' => __( 'Customer ID if registered. Will return 0 for guests.', 'woo-gutenberg-products-block' ),
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
				'properties'  => $this->force_schema_readonly( $this->billing_address_schema->get_properties() ),
			],
			'shipping_address'   => [
				'description' => __( 'Shipping address.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => $this->force_schema_readonly( $this->shipping_address_schema->get_properties() ),
			],
			'coupons'            => [
				'description' => __( 'List of applied coupons.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( $this->coupon_schema->get_properties() ),
				],
			],
			'items'              => [
				'description' => __( 'List of cart items.', 'woo-gutenberg-products-block' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( $this->item_schema->get_properties() ),
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
		return [
			'id'                 => $order->get_id(),
			'number'             => $order->get_order_number(),
			'status'             => $order->get_status(),
			'order_key'          => $order->get_order_key(),
			'created_via'        => $order->get_created_via(),
			'prices_include_tax' => $order->get_prices_include_tax(),
			'events'             => (object) $this->get_events( $order ),
			'customer'           => (object) [
				'customer_id'         => $order->get_customer_id(),
				'customer_ip_address' => $order->get_customer_ip_address(),
				'customer_user_agent' => $order->get_customer_user_agent(),
			],
			'customer_note'      => $order->get_customer_note(),
			'billing_address'    => $this->billing_address_schema->get_item_response( $order ),
			'shipping_address'   => $this->shipping_address_schema->get_item_response( $order ),
			'coupons'            => array_values( array_map( [ $this->coupon_schema, 'get_item_response' ], $order->get_items( 'coupon' ) ) ),
			'items'              => array_values( array_map( [ $this->item_schema, 'get_item_response' ], $order->get_items( 'line_item' ) ) ),
			'totals'             => (object) array_merge(
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
	 * @todo Remove once https://github.com/woocommerce/woocommerce/pull/26101 is merged and released in core.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_subtotal_tax( \WC_Order $order ) {
		if ( method_exists( $order, 'get_subtotal_tax' ) ) {
			return $order->get_subtotal_tax();
		}

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
	 * @todo Remove once https://github.com/woocommerce/woocommerce/pull/26101 is merged and released in core.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_fee_total( \WC_Order $order ) {
		if ( method_exists( $order, 'get_fee_total' ) ) {
			return $order->get_fee_total();
		}

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
	 * @todo Remove once https://github.com/woocommerce/woocommerce/pull/26101 is merged and released in core.
	 *
	 * @param \WC_Order $order Order class instance.
	 * @return float
	 */
	protected function get_fee_tax( \WC_Order $order ) {
		if ( method_exists( $order, 'get_fee_tax' ) ) {
			return $order->get_fee_tax();
		}

		$total = 0;
		foreach ( $order->get_fees() as $item ) {
			$total += $item->get_total_tax();
		}
		return $total;
	}
}
