<?php
/**
 * CheckoutSessionSchema class.
 *
 * @package Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic
 */

declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\CheckoutSessionStatus;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\MessageType;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\MessageContentType;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\FulfillmentType;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\TotalType;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\LinkType;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\PaymentMethod;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\AgenticCheckoutSession;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use WC_Order;

/**
 * Handles the schema for Agentic Checkout API checkout sessions.
 * This schema formats WooCommerce cart/order data according to the
 * Agentic Commerce Protocol specification.
 *
 * @internal The specification for agentic requests is subject to abrupt changes; backwards compatibility cannot be guaranteed.
 */
class CheckoutSessionSchema extends AbstractSchema {
	use DraftOrderTrait;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'agentic_checkout_session';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'agentic-checkout-session';

	/**
	 * Checkout session schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'                    => [
				'description' => __( 'Unique identifier for the checkout session.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'buyer'                 => [
				'description' => __( 'Buyer information.', 'woocommerce' ),
				'type'        => [ 'object', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'first_name'   => [
						'description' => __( 'First name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'last_name'    => [
						'description' => __( 'Last name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'email'        => [
						'description' => __( 'Email address.', 'woocommerce' ),
						'type'        => 'string',
					],
					'phone_number' => [
						'description' => __( 'Phone number.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'payment_provider'      => [
				'description' => __( 'Payment provider information.', 'woocommerce' ),
				'type'        => [ 'object', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'provider'                  => [
						'description' => __( 'Payment provider identifier.', 'woocommerce' ),
						'type'        => 'string',
					],
					'supported_payment_methods' => [
						'description' => __( 'List of supported payment methods.', 'woocommerce' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
						],
					],
				],
			],
			'status'                => [
				'description' => __( 'Status of the checkout session.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'enum'        => [
					CheckoutSessionStatus::NOT_READY_FOR_PAYMENT,
					CheckoutSessionStatus::READY_FOR_PAYMENT,
					CheckoutSessionStatus::COMPLETED,
					CheckoutSessionStatus::CANCELED,
				],
				'readonly'    => true,
			],
			'currency'              => [
				'description' => __( 'Currency code (ISO 4217).', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'line_items'            => [
				'description' => __( 'Line items in the checkout session.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'          => [
							'description' => __( 'Line item ID.', 'woocommerce' ),
							'type'        => 'string',
						],
						'item'        => [
							'description' => __( 'Product item details.', 'woocommerce' ),
							'type'        => 'object',
							'properties'  => [
								'id'       => [
									'description' => __( 'Product ID.', 'woocommerce' ),
									'type'        => 'string',
								],
								'quantity' => [
									'description' => __( 'Quantity.', 'woocommerce' ),
									'type'        => 'integer',
								],
							],
						],
						'base_amount' => [
							'description' => __( 'Base amount in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'discount'    => [
							'description' => __( 'Discount amount in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'subtotal'    => [
							'description' => __( 'Subtotal in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'tax'         => [
							'description' => __( 'Tax amount in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'total'       => [
							'description' => __( 'Total amount in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
					],
				],
			],
			'fulfillment_address'   => [
				'description' => __( 'Fulfillment/shipping address.', 'woocommerce' ),
				'type'        => [ 'object', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'name'        => [
						'description' => __( 'Full name.', 'woocommerce' ),
						'type'        => 'string',
					],
					'line_one'    => [
						'description' => __( 'Address line 1.', 'woocommerce' ),
						'type'        => 'string',
					],
					'line_two'    => [
						'description' => __( 'Address line 2.', 'woocommerce' ),
						'type'        => [ 'string', 'null' ],
					],
					'city'        => [
						'description' => __( 'City.', 'woocommerce' ),
						'type'        => 'string',
					],
					'state'       => [
						'description' => __( 'State/province.', 'woocommerce' ),
						'type'        => 'string',
					],
					'country'     => [
						'description' => __( 'Country code (ISO 3166-1 alpha-2).', 'woocommerce' ),
						'type'        => 'string',
					],
					'postal_code' => [
						'description' => __( 'Postal/ZIP code.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
			],
			'fulfillment_options'   => [
				'description' => __( 'Available fulfillment options.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'type'                   => [
							'description' => __( 'Fulfillment type.', 'woocommerce' ),
							'type'        => 'string',
							'enum'        => [ FulfillmentType::SHIPPING, FulfillmentType::DIGITAL ],
						],
						'id'                     => [
							'description' => __( 'Fulfillment option ID.', 'woocommerce' ),
							'type'        => 'string',
						],
						'title'                  => [
							'description' => __( 'Title.', 'woocommerce' ),
							'type'        => 'string',
						],
						'subtitle'               => [
							'description' => __( 'Subtitle.', 'woocommerce' ),
							'type'        => [ 'string', 'null' ],
						],
						'carrier'                => [
							'description' => __( 'Carrier name.', 'woocommerce' ),
							'type'        => [ 'string', 'null' ],
						],
						'earliest_delivery_time' => [
							'description' => __( 'Earliest delivery time (ISO 8601).', 'woocommerce' ),
							'type'        => [ 'string', 'null' ],
						],
						'latest_delivery_time'   => [
							'description' => __( 'Latest delivery time (ISO 8601).', 'woocommerce' ),
							'type'        => [ 'string', 'null' ],
						],
						'subtotal'               => [
							'description' => __( 'Subtotal in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'tax'                    => [
							'description' => __( 'Tax in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
						'total'                  => [
							'description' => __( 'Total in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
					],
				],
			],
			'fulfillment_option_id' => [
				'description' => __( 'Selected fulfillment option ID.', 'woocommerce' ),
				'type'        => [ 'string', 'null' ],
				'context'     => [ 'view', 'edit' ],
			],
			'totals'                => [
				'description' => __( 'Order totals breakdown.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'type'         => [
							'description' => __( 'Total type.', 'woocommerce' ),
							'type'        => 'string',
						],
						'display_text' => [
							'description' => __( 'Display text.', 'woocommerce' ),
							'type'        => 'string',
						],
						'amount'       => [
							'description' => __( 'Amount in cents.', 'woocommerce' ),
							'type'        => 'integer',
						],
					],
				],
			],
			'messages'              => [
				'description' => __( 'Messages (info, warnings, errors).', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'type'         => [
							'description' => __( 'Message type.', 'woocommerce' ),
							'type'        => 'string',
							'enum'        => [ MessageType::INFO, MessageType::WARNING, MessageType::ERROR ],
						],
						'param'        => [
							'description' => __( 'JSON path to the related field.', 'woocommerce' ),
							'type'        => [ 'string', 'null' ],
						],
						'content_type' => [
							'description' => __( 'Content type.', 'woocommerce' ),
							'type'        => 'string',
							'enum'        => [ MessageContentType::PLAIN, MessageContentType::MARKDOWN ],
						],
						'content'      => [
							'description' => __( 'Message content.', 'woocommerce' ),
							'type'        => 'string',
						],
					],
				],
			],
			'links'                 => [
				'description' => __( 'Related links.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'type' => [
							'description' => __( 'Link type.', 'woocommerce' ),
							'type'        => 'string',
						],
						'url'  => [
							'description' => __( 'URL.', 'woocommerce' ),
							'type'        => 'string',
						],
					],
				],
			],
		];
	}

	/**
	 * Convert a WooCommerce cart to the Agentic Checkout session format.
	 *
	 * @param AgenticCheckoutSession $checkout_session Checkout session object.
	 * @return array Formatted checkout session data.
	 */
	public function get_item_response( $checkout_session ) {
		$cart = $checkout_session->get_cart();

		// If validation already went through and we have errors, no need to repeat them.
		if ( ! $checkout_session->get_messages()->has_errors() ) {
			// Validate the checkout session. Messages will be added to the collection, if any.
			AgenticCheckoutUtils::validate( $checkout_session );
		}

		$completed_order = WC()->session
			? wc_get_order( WC()->session->get( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID ) )
			: null;

		// Get line items from cart, or from completed order if cart is empty.
		$cart_items = $cart->get_cart();
		$line_items = $completed_order instanceof WC_Order
			? $this->format_line_items_from_order( $completed_order )
			: $this->format_line_items_from_cart( $cart_items );

		$response = [
			'id'                    => $checkout_session->get_id(),
			'buyer'                 => $completed_order instanceof WC_Order
				? $this->format_buyer_from_order( $completed_order )
				: $this->format_buyer(),
			'payment_provider'      => $this->format_payment_provider(),
			'status'                => AgenticCheckoutUtils::calculate_status( $checkout_session ),
			'currency'              => $completed_order instanceof WC_Order
				? strtolower( $completed_order->get_currency() )
				: strtolower( get_woocommerce_currency() ),
			'line_items'            => $line_items,
			'fulfillment_address'   => $completed_order instanceof WC_Order
				? $this->format_fulfillment_address_from_order( $completed_order )
				: $this->format_fulfillment_address(),
			'fulfillment_options'   => $completed_order instanceof WC_Order
				? $this->format_fulfillment_options_from_order( $completed_order )
				: $this->format_fulfillment_options(),
			'fulfillment_option_id' => $completed_order instanceof WC_Order
				? $this->get_selected_fulfillment_option_id_from_order( $completed_order )
				: $this->get_selected_fulfillment_option_id(),
			'totals'                => $completed_order instanceof WC_Order
				? $this->format_totals_from_order( $completed_order )
				: $this->format_totals( $cart ),
			'messages'              => $checkout_session->get_messages()->get_formatted_messages(),
			'links'                 => $this->get_links(),
		];

		// Add order data if a completed order exists.
		if ( $completed_order instanceof WC_Order ) {
			$response['order'] = [
				'id'                  => (string) $completed_order->get_id(),
				'checkout_session_id' => $checkout_session->get_id(),
				'permalink_url'       => $completed_order->get_checkout_order_received_url(),
			];
		}

		return $response;
	}

	/**
	 * Format buyer information.
	 *
	 * @return array|null Buyer data or null.
	 */
	protected function format_buyer() {
		$customer = WC()->customer;

		if ( ! $customer ) {
			return null;
		}

		$first_name = $customer->get_billing_first_name() ? $customer->get_billing_first_name() : $customer->get_shipping_first_name();
		$last_name  = $customer->get_billing_last_name() ? $customer->get_billing_last_name() : $customer->get_shipping_last_name();
		$email      = $customer->get_billing_email();

		if ( ! $first_name && ! $last_name && ! $email ) {
			return null;
		}

		return [
			'first_name'   => $first_name ? $first_name : '',
			'last_name'    => $last_name ? $last_name : '',
			'email'        => $email ? $email : '',
			'phone_number' => $customer->get_billing_phone() ? $customer->get_billing_phone() : '',
		];
	}

	/**
	 * Format buyer information from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null Buyer data or null.
	 */
	protected function format_buyer_from_order( $order ) {
		$first_name = $order->get_billing_first_name() ? $order->get_billing_first_name() : $order->get_shipping_first_name();
		$last_name  = $order->get_billing_last_name() ? $order->get_billing_last_name() : $order->get_shipping_last_name();
		$email      = $order->get_billing_email();

		if ( ! $first_name && ! $last_name && ! $email ) {
			return null;
		}

		return [
			'first_name'   => $first_name ? $first_name : '',
			'last_name'    => $last_name ? $last_name : '',
			'email'        => $email ? $email : '',
			'phone_number' => $order->get_billing_phone() ? $order->get_billing_phone() : '',
		];
	}

	/**
	 * Format payment provider information.
	 *
	 * @return array|null Payment provider data or null.
	 */
	protected function format_payment_provider() {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			return null;
		}

		// Look for gateway with agentic_commerce capability.
		$gateway = AgenticCheckoutUtils::get_agentic_commerce_gateway( $available_gateways );

		if ( null !== $gateway ) {
			return [
				'provider'                  => $gateway->get_agentic_commerce_provider(),
				'supported_payment_methods' => $gateway->get_agentic_commerce_payment_methods(),
			];
		}

		return [
			'provider'                  => 'stripe',
			'supported_payment_methods' => [ PaymentMethod::CARD ], // Default, can be expanded.
		];
	}

	/**
	 * Convert amount from decimal to cents.
	 *
	 * @param string|float $amount Amount in decimal.
	 * @return int Amount in cents.
	 */
	protected function amount_to_cents( $amount ) {
		return (int) $this->extend->get_formatter( 'money' )->format(
			$amount
		);
	}

	/**
	 * Format line items from cart.
	 *
	 * @param array $cart_items Cart items array.
	 * @return array Formatted line items.
	 */
	protected function format_line_items_from_cart( $cart_items ) {
		$items = [];

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product     = $cart_item['data'];
			$quantity    = $cart_item['quantity'];
			$base_amount = $this->amount_to_cents( $product->get_price() * $quantity );
			$discount    = $this->amount_to_cents( $cart_item['line_subtotal'] - $cart_item['line_total'] );
			$subtotal    = $base_amount - $discount;
			$tax         = $this->amount_to_cents( $cart_item['line_tax'] );
			$total       = $subtotal + $tax;

			$items[] = [
				'id'          => (string) $cart_item_key,
				'item'        => [
					'id'       => (string) $product->get_id(),
					'quantity' => $quantity,
				],
				'base_amount' => $base_amount,
				'discount'    => $discount,
				'subtotal'    => $subtotal,
				'tax'         => $tax,
				'total'       => $total,
			];
		}

		return $items;
	}

	/**
	 * Format line items from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array Formatted line items.
	 */
	protected function format_line_items_from_order( $order ) {
		$items = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			$quantity    = $item->get_quantity();
			$base_amount = $this->amount_to_cents( $item->get_subtotal() );
			$discount    = $this->amount_to_cents( $item->get_subtotal() - $item->get_total() );
			$subtotal    = $base_amount - $discount;
			$tax         = $this->amount_to_cents( $item->get_total_tax() );
			$total       = $subtotal + $tax;

			// Use product_id from the order item, with variation_id as fallback.
			$item_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

			$items[] = [
				'id'          => (string) $item_id,
				'item'        => [
					'id'       => (string) $item_product_id,
					'quantity' => $quantity,
				],
				'base_amount' => $base_amount,
				'discount'    => $discount,
				'subtotal'    => $subtotal,
				'tax'         => $tax,
				'total'       => $total,
			];
		}

		return $items;
	}

	/**
	 * Format fulfillment address.
	 *
	 * @return array|null Address data or null.
	 */
	protected function format_fulfillment_address() {
		$customer = WC()->customer;

		if ( ! $customer || ! $customer->get_shipping_address_1() ) {
			return null;
		}

		return $this->build_address_array(
			$customer->get_shipping_first_name(),
			$customer->get_shipping_last_name(),
			$customer->get_shipping_address_1(),
			$customer->get_shipping_address_2(),
			$customer->get_shipping_city(),
			$customer->get_shipping_state(),
			$customer->get_shipping_country(),
			$customer->get_shipping_postcode()
		);
	}

	/**
	 * Format fulfillment address from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null Address data or null.
	 */
	protected function format_fulfillment_address_from_order( $order ) {
		if ( ! $order->get_shipping_address_1() ) {
			return null;
		}

		return $this->build_address_array(
			$order->get_shipping_first_name(),
			$order->get_shipping_last_name(),
			$order->get_shipping_address_1(),
			$order->get_shipping_address_2(),
			$order->get_shipping_city(),
			$order->get_shipping_state(),
			$order->get_shipping_country(),
			$order->get_shipping_postcode()
		);
	}

	/**
	 * Build address array from components.
	 *
	 * @param string $first_name First name.
	 * @param string $last_name Last name.
	 * @param string $address_1 Address line 1.
	 * @param string $address_2 Address line 2.
	 * @param string $city City.
	 * @param string $state State.
	 * @param string $country Country.
	 * @param string $postcode Postcode.
	 * @return array Address array.
	 */
	protected function build_address_array( $first_name, $last_name, $address_1, $address_2, $city, $state, $country, $postcode ) {
		$name = trim( $first_name . ' ' . $last_name );

		return [
			'name'        => $name ? $name : 'Customer',
			'line_one'    => $address_1,
			'line_two'    => $address_2 ? $address_2 : '',
			'city'        => $city,
			'state'       => $state,
			'country'     => $country,
			'postal_code' => $postcode,
		];
	}

	/**
	 * Format fulfillment options (shipping methods).
	 *
	 * @return array Fulfillment options.
	 */
	protected function format_fulfillment_options() {
		$options  = [];
		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $package ) {
			if ( empty( $package['rates'] ) ) {
				continue;
			}

			foreach ( $package['rates'] as $rate ) {
				$options[] = [
					'type'                   => FulfillmentType::SHIPPING,
					'id'                     => $rate->get_id(),
					'title'                  => $rate->get_label(),
					'subtitle'               => null,
					'carrier'                => $rate->get_method_id(),
					'earliest_delivery_time' => null,
					'latest_delivery_time'   => null,
					'subtotal'               => $this->amount_to_cents( $rate->get_cost() ),
					'tax'                    => $this->amount_to_cents( $rate->get_shipping_tax() ),
					'total'                  => $this->amount_to_cents( $rate->get_cost() + $rate->get_shipping_tax() ),
				];
			}
		}

		return $options;
	}

	/**
	 * Format fulfillment options from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array Fulfillment options.
	 */
	protected function format_fulfillment_options_from_order( $order ) {
		$options          = [];
		$shipping_methods = $order->get_shipping_methods();

		foreach ( $shipping_methods as $item ) {
			$options[] = [
				'type'                   => FulfillmentType::SHIPPING,
				'id'                     => $item->get_method_id() . ':' . $item->get_instance_id(),
				'title'                  => $item->get_name(),
				'subtitle'               => null,
				'carrier'                => $item->get_method_id(),
				'earliest_delivery_time' => null,
				'latest_delivery_time'   => null,
				'subtotal'               => $this->amount_to_cents( $item->get_total() ),
				'tax'                    => $this->amount_to_cents( $item->get_total_tax() ),
				'total'                  => $this->amount_to_cents( $item->get_total() + $item->get_total_tax() ),
			];
		}

		return $options;
	}

	/**
	 * Get selected fulfillment option ID.
	 *
	 * @return string|null Selected option ID or null.
	 */
	protected function get_selected_fulfillment_option_id() {
		$chosen_methods = WC()->session->get( SessionKey::CHOSEN_SHIPPING_METHODS );

		return ! empty( $chosen_methods[0] ) ? $chosen_methods[0] : null;
	}

	/**
	 * Get selected fulfillment option ID from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string|null Selected option ID or null.
	 */
	protected function get_selected_fulfillment_option_id_from_order( $order ) {
		$shipping_methods = $order->get_shipping_methods();
		if ( empty( $shipping_methods ) ) {
			return null;
		}

		$shipping_method = reset( $shipping_methods );
		return $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id();
	}

	/**
	 * Format totals array.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return array Totals array.
	 */
	protected function format_totals( $cart ) {
		$totals = [];

		// Items base amount.
		$items_base = 0;
		foreach ( $cart->get_cart() as $cart_item ) {
			$product     = $cart_item['data'];
			$items_base += $product->get_price() * $cart_item['quantity'];
		}
		$totals[] = [
			'type'         => TotalType::ITEMS_BASE_AMOUNT,
			'display_text' => __( 'Items Base Amount', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $items_base ),
		];

		// Items discount.
		$discount = $cart->get_cart_discount_total();
		$totals[] = [
			'type'         => TotalType::ITEMS_DISCOUNT,
			'display_text' => __( 'Items Discount', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $discount ),
		];

		// Subtotal.
		$totals[] = [
			'type'         => TotalType::SUBTOTAL,
			'display_text' => __( 'Subtotal', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $cart->get_subtotal() - $discount ),
		];

		// Fulfillment (shipping).
		$totals[] = [
			'type'         => TotalType::FULFILLMENT,
			'display_text' => __( 'Shipping', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $cart->get_shipping_total() ),
		];

		// Tax.
		$totals[] = [
			'type'         => TotalType::TAX,
			'display_text' => __( 'Tax', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $cart->get_total_tax() ),
		];

		// Total.
		$totals[] = [
			'type'         => TotalType::TOTAL,
			'display_text' => __( 'Total', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $cart->get_total( 'edit' ) ),
		];

		return $totals;
	}

	/**
	 * Format totals array from order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array Totals array.
	 */
	protected function format_totals_from_order( $order ) {
		$totals = [];

		// Items base amount.
		$items_base = 0;
		foreach ( $order->get_items() as $item ) {
			$product     = $item->get_product();
			$items_base += $product->get_price() * $item->get_quantity();
		}
		$totals[] = [
			'type'         => TotalType::ITEMS_BASE_AMOUNT,
			'display_text' => __( 'Items Base Amount', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $items_base ),
		];

		// Items discount.
		$discount = $order->get_discount_total();
		$totals[] = [
			'type'         => TotalType::ITEMS_DISCOUNT,
			'display_text' => __( 'Items Discount', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $discount ),
		];

		// Subtotal.
		$totals[] = [
			'type'         => TotalType::SUBTOTAL,
			'display_text' => __( 'Subtotal', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $items_base - $discount ),
		];

		// Fulfillment (shipping).
		$totals[] = [
			'type'         => TotalType::FULFILLMENT,
			'display_text' => __( 'Shipping', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $order->get_shipping_total() ),
		];

		// Tax.
		$totals[] = [
			'type'         => TotalType::TAX,
			'display_text' => __( 'Tax', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $order->get_total_tax() ),
		];

		// Total.
		$totals[] = [
			'type'         => TotalType::TOTAL,
			'display_text' => __( 'Total', 'woocommerce' ),
			'amount'       => $this->amount_to_cents( $order->get_total() ),
		];

		return $totals;
	}

	/**
	 * Get links for the session.
	 *
	 * @return array Links array.
	 */
	protected function get_links() {
		$links = [];

		// Terms of use.
		$terms_page_id = wc_terms_and_conditions_page_id();
		if ( $terms_page_id ) {
			$permalink = get_permalink( $terms_page_id );
			if ( $permalink ) {
				$links[] = [
					'type' => LinkType::TERMS_OF_USE,
					'url'  => $permalink,
				];
			}
		}

		// Privacy policy.
		$privacy_page_id = get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy_page_id ) {
			$permalink = get_permalink( $privacy_page_id );
			if ( $permalink ) {
				$links[] = [
					'type' => LinkType::PRIVACY_POLICY,
					'url'  => $permalink,
				];
			}
		}

		return $links;
	}
}
