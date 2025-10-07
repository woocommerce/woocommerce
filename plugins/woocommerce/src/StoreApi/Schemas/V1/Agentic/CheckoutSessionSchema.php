<?php
/**
 * CheckoutSessionSchema class.
 *
 * @package Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic
 */

declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\CheckoutSessionStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\MessageType;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\MessageContentType;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\FulfillmentType;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\TotalType;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\LinkType;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\PaymentMethod;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;

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
	 * @param mixed $cart_data Cart data from WooCommerce (unused, uses WC()->cart directly).
	 * @return array Formatted checkout session data.
	 */
	public function get_item_response( $cart_data ) {
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable.
		$cart = WC()->cart;

		// Get draft order if exists.
		$draft_order = $this->get_draft_order();

		// Generate session ID from Cart-Token.
		$session_id = WC()->session->get( SessionKey::AGENTIC_SESSION_ID );
		if ( null === $session_id ) {
			$session_id = CartTokenUtils::get_cart_token( (string) WC()->session->get_customer_id() );
			WC()->session->set( SessionKey::AGENTIC_SESSION_ID, $session_id );
		}

		return [
			'id'                    => $session_id,
			'buyer'                 => $this->format_buyer(),
			'payment_provider'      => $this->format_payment_provider(),
			'status'                => $this->calculate_status( $cart, $draft_order ),
			'currency'              => strtolower( get_woocommerce_currency() ),
			'line_items'            => $this->format_line_items( $cart->get_cart() ),
			'fulfillment_address'   => $this->format_fulfillment_address(),
			'fulfillment_options'   => $this->format_fulfillment_options(),
			'fulfillment_option_id' => $this->get_selected_fulfillment_option_id(),
			'totals'                => $this->format_totals( $cart ),
			'messages'              => $this->get_messages( $cart ),
			'links'                 => $this->get_links(),
		];
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

		$first_name = $customer->get_billing_first_name();
		$last_name  = $customer->get_billing_last_name();
		$email      = $customer->get_billing_email();
		$phone      = $customer->get_billing_phone();

		if ( ! $first_name && ! $last_name && ! $email ) {
			return null;
		}

		return [
			'first_name'   => $first_name ? $first_name : '',
			'last_name'    => $last_name ? $last_name : '',
			'email'        => $email ? $email : '',
			'phone_number' => $phone ? $phone : '',
		];
	}

	/**
	 * Format payment provider information.
	 *
	 * @return array|null Payment provider data or null.
	 */
	protected function format_payment_provider() {
		// Default to first available payment gateway.
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			return null;
		}

		$first_gateway = reset( $available_gateways );

		return [
			'provider'                  => $first_gateway->id,
			'supported_payment_methods' => [ PaymentMethod::CARD ], // Default, can be expanded.
		];
	}

	/**
	 * Calculate the status of the checkout session.
	 *
	 * @param \WC_Cart       $cart Cart object.
	 * @param \WC_Order|null $order Draft order if exists.
	 * @return string Status value.
	 */
	protected function calculate_status( $cart, $order ) {
		// Check if canceled.
		if ( $order && $order->get_meta( OrderMetaKey::AGENTIC_CHECKOUT_CANCELED ) === 'yes' ) {
			return CheckoutSessionStatus::CANCELED;
		}

		// Check if completed (only processing and completed are final statuses).
		if ( $order && in_array( $order->get_status(), [ OrderStatus::PROCESSING, OrderStatus::COMPLETED ], true ) ) {
			return CheckoutSessionStatus::COMPLETED;
		}

		// Check if pending (payment not yet cleared).
		if ( $order && OrderStatus::PENDING === $order->get_status() ) {
			return CheckoutSessionStatus::READY_FOR_PAYMENT;
		}

		// Check if ready for payment.
		$needs_shipping = $cart->needs_shipping();
		$has_address    = WC()->customer && WC()->customer->get_shipping_address_1();

		// Check if valid shipping method is selected (not just empty strings).
		$chosen_methods = WC()->session ? WC()->session->get( SessionKey::CHOSEN_SHIPPING_METHODS ) : null;
		$has_shipping   = ! empty( $chosen_methods ) && ! empty( array_filter( $chosen_methods ) );

		if ( $needs_shipping && ( ! $has_address || ! $has_shipping ) ) {
			return CheckoutSessionStatus::NOT_READY_FOR_PAYMENT;
		}

		// Check for cart validation errors.
		if ( ! empty( wc_get_notices( 'error' ) ) ) {
			return CheckoutSessionStatus::NOT_READY_FOR_PAYMENT;
		}

		return CheckoutSessionStatus::READY_FOR_PAYMENT;
	}

	/**
	 * Convert amount from decimal to cents.
	 *
	 * @param string|float $amount Amount in decimal.
	 * @return int Amount in cents.
	 */
	protected function amount_to_cents( $amount ) {
		return (int) $this->extend->get_formatter( 'money' )->format(
			$amount,
			[ 'decimals' => 2 ]
		);
	}

	/**
	 * Format line items from cart.
	 *
	 * @param array $cart_items Cart items array.
	 * @return array Formatted line items.
	 */
	protected function format_line_items( $cart_items ) {
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
	 * Format fulfillment address.
	 *
	 * @return array|null Address data or null.
	 */
	protected function format_fulfillment_address() {
		$customer = WC()->customer;

		if ( ! $customer || ! $customer->get_shipping_address_1() ) {
			return null;
		}

		$name = trim( $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name() );

		return [
			'name'        => $name ? $name : 'Customer',
			'line_one'    => $customer->get_shipping_address_1(),
			'line_two'    => $customer->get_shipping_address_2() ? $customer->get_shipping_address_2() : '',
			'city'        => $customer->get_shipping_city(),
			'state'       => $customer->get_shipping_state(),
			'country'     => $customer->get_shipping_country(),
			'postal_code' => $customer->get_shipping_postcode(),
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
	 * Get selected fulfillment option ID.
	 *
	 * @return string|null Selected option ID or null.
	 */
	protected function get_selected_fulfillment_option_id() {
		$chosen_methods = WC()->session->get( SessionKey::CHOSEN_SHIPPING_METHODS );

		return ! empty( $chosen_methods[0] ) ? $chosen_methods[0] : null;
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
	 * Get messages for the session.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return array Messages array.
	 */
	protected function get_messages( $cart ) {
		$messages = [];

		// Add info message if shipping is needed.
		if ( $cart->needs_shipping() && ! WC()->customer->get_shipping_address_1() ) {
			$messages[] = [
				'type'         => MessageType::INFO,
				'param'        => '$.fulfillment_address',
				'content_type' => MessageContentType::PLAIN,
				'content'      => __( 'Shipping address required.', 'woocommerce' ),
			];
		}

		return $messages;
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
