<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Schemas\V1\OrderSchema;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\CheckoutMapper;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * Shared behaviour of the UCP checkout routes.
 *
 * A checkout is a cart session promoted through `POST /checkout-sessions`, so its id is the
 * cart id and every cart route rule applies: path-bound token session, no nonce, last-wins
 * writes. The checkout's own state sits beside the cart in the WC session.
 */
abstract class AbstractCheckoutRoute extends AbstractCartRoute {
	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ucp-checkout';

	/**
	 * The capability this route answers for.
	 *
	 * @return string
	 */
	protected function capability(): string {
		return Utils::CAPABILITY_CHECKOUT;
	}

	/**
	 * Build the `ucp` envelope, with the `payment_handlers` registry a checkout response requires.
	 *
	 * Empty: these routes take no payment, and `complete` hands the buyer to the store instead.
	 *
	 * @param string $status Response status, `success` or `error`.
	 * @return array
	 */
	protected function ucp_metadata( string $status ): array {
		$metadata                     = parent::ucp_metadata( $status );
		$metadata['payment_handlers'] = new \stdClass();

		return $metadata;
	}

	/**
	 * Build the response for a checkout id that does not resolve.
	 *
	 * Unlike the cart routes, a 404: the checkout REST binding names it as a transport error.
	 *
	 * @return \WP_REST_Response
	 */
	protected function ucp_not_found(): \WP_REST_Response {
		return $this->ucp_error( 'not_found', __( 'Checkout session not found.', 'woocommerce' ), 'unrecoverable', 404, '$.id' );
	}

	/**
	 * Load the state of the checkout the route path names, refusing one that cannot be acted on.
	 *
	 * @param bool $for_update Whether the request changes the checkout, which a completed, canceled or paid-but-unconfirmed one refuses.
	 * @return array<string, mixed>|\WP_REST_Response The state, or the 404/409 error response.
	 */
	protected function open_checkout( bool $for_update ) {
		$state = $this->checkout_state();

		if ( null === $state ) {
			return $this->ucp_not_found();
		}

		if ( $for_update ) {
			if ( $this->is_terminal( $state ) ) {
				return $this->ucp_conflict();
			}

			if ( $this->awaits_confirmation( $state ) ) {
				return $this->ucp_conflict( __( 'This checkout session is awaiting confirmation of a payment the buyer already made.', 'woocommerce' ) );
			}
		}

		return $state;
	}

	/**
	 * Whether the handed-off order holds a payment the store has yet to confirm, such as a bank transfer.
	 *
	 * The buyer paid in the browser, where the cart would have been emptied; this session was not, so
	 * it is locked until the store accepts or cancels that payment, or a second order would be made.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 * @return bool
	 */
	protected function awaits_confirmation( array $state ): bool {
		$order = empty( $state['order_id'] ) ? null : wc_get_order( (int) $state['order_id'] );

		return $order instanceof \WC_Order && ! $order->has_status( $this->payable_statuses( $order ) );
	}

	/**
	 * The statuses in which the payment page still takes an order, as `WC_Order::needs_payment()` lists them.
	 *
	 * @param \WC_Order $order The order.
	 * @return array<string>
	 */
	protected function payable_statuses( \WC_Order $order ): array {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook, documented in WC_Order::needs_payment().
		return (array) apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( OrderStatus::PENDING, OrderStatus::FAILED ), $order );
	}

	/**
	 * Cancel the order handed to the buyer while its payment page would still take it, and forget the hand-off.
	 *
	 * Once the checkout changes or ends, that order no longer matches it and must not be paid.
	 * The state is saved without the hand-off, unless the order was paid meanwhile and stays.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 * @param string               $note Order note explaining the cancellation.
	 * @return array<string, mixed> The state without the hand-off, or unchanged, `order_id` included, when the order was paid meanwhile or could not be cancelled.
	 */
	protected function cancel_handed_off_order( array $state, string $note ): array {
		$order = empty( $state['order_id'] ) ? null : wc_get_order( (int) $state['order_id'] );

		if ( $order instanceof \WC_Order ) {
			// Paid, or put on hold, since the request began: the checkout keeps its order and hand-off.
			if ( ! $order->has_status( $this->payable_statuses( $order ) ) ) {
				return $state;
			}

			// The payment page's own list, so a failed attempt the buyer could retry is closed too.
			// A cancellation that did not save leaves the order payable, so the checkout goes on tracking it.
			if ( ! $order->update_status( OrderStatus::CANCELLED, $note ) ) {
				return $state;
			}

			if ( (int) $this->get_draft_order_id() === $order->get_id() ) {
				$this->set_draft_order_id( 0 );
			}
		}

		unset( $state['order_id'], $state['fingerprint'], $state['status'], $state['continue_url'] );
		$this->save_checkout_state( $state );

		return $state;
	}

	/**
	 * Whether the handed-off order can still be paid, as it can after a cancellation that failed.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 * @return bool
	 */
	protected function hand_off_is_payable( array $state ): bool {
		$order = empty( $state['order_id'] ) ? null : wc_get_order( (int) $state['order_id'] );

		return $order instanceof \WC_Order && $order->has_status( $this->payable_statuses( $order ) );
	}

	/**
	 * Refuse a request whose order was paid, or put on hold, while it ran, as if it had arrived a moment later.
	 *
	 * An order still payable is one that could not be cancelled, which a retry may yet do.
	 *
	 * @return \WP_REST_Response
	 */
	protected function refuse_kept_order(): \WP_REST_Response {
		$refusal = $this->open_checkout( true );

		if ( $refusal instanceof \WP_REST_Response ) {
			return $refusal;
		}

		if ( $this->hand_off_is_payable( $refusal ) ) {
			return $this->ucp_error( 'TRANSPORT_ERROR', __( 'The order handed off earlier could not be canceled. Please try again.', 'woocommerce' ), 'recoverable', 500 );
		}

		return $this->ucp_conflict();
	}

	/**
	 * Fingerprint what a handed-off order is built from: cart contents and total, addresses and shipping choice.
	 *
	 * @return string
	 */
	protected function hand_off_fingerprint(): string {
		return hash(
			'sha256',
			(string) wp_json_encode(
				array(
					$this->cart_controller->get_cart_instance()->get_cart_hash(),
					WC()->customer->get_billing(),
					WC()->customer->get_shipping(),
					WC()->session->get( 'chosen_shipping_methods' ),
				)
			)
		);
	}

	/**
	 * Store the checkout state in the bound session.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 */
	protected function save_checkout_state( array $state ): void {
		unset( $state['order'] );
		WC()->session->set( self::CHECKOUT_SESSION_KEY, $state );
	}

	/**
	 * Build the response refusing a change to a checkout in a state that does not take one.
	 *
	 * @param string $message Why the change is refused; defaults to the checkout being completed or canceled.
	 * @return \WP_REST_Response
	 */
	protected function ucp_conflict( string $message = '' ): \WP_REST_Response {
		if ( '' === $message ) {
			$message = __( 'This checkout session is already completed or canceled.', 'woocommerce' );
		}

		return $this->ucp_error( 'CONFLICT', $message, 'unrecoverable', 409, '$.status' );
	}

	/**
	 * Build a successful checkout response.
	 *
	 * A paid checkout is read from its order: the cart session lives on and follows later price and stock changes.
	 * So is one whose payment awaits the store's confirmation, which reads as `complete_in_progress`.
	 * A cart changed behind a hand-off voids it for the read too, until an update or complete cancels the order.
	 *
	 * @param string               $checkout_id Checkout identifier.
	 * @param array<string, mixed> $state Checkout state.
	 * @param int                  $http_status HTTP status.
	 * @param array|null           $cart Store API cart response already built for this request, or null to build it.
	 * @return \WP_REST_Response
	 */
	protected function checkout_response( string $checkout_id, array $state, int $http_status = 200, ?array $cart = null ): \WP_REST_Response {
		$order_id = (int) ( $state['order']['id'] ?? 0 );

		if ( ! $order_id && $this->awaits_confirmation( $state ) ) {
			// The buyer has paid, so the payment page must not be offered again while the store confirms.
			$order_id        = (int) $state['order_id'];
			$state['status'] = CheckoutMapper::STATUS_COMPLETE_IN_PROGRESS;
			unset( $state['continue_url'] );
		}

		$order = $order_id ? wc_get_order( $order_id ) : null;

		if ( $order instanceof \WC_Order ) {
			$cart = $this->order_as_cart( $order );
		} elseif ( ! empty( $state['continue_url'] ) && ( $state['fingerprint'] ?? '' ) !== $this->hand_off_fingerprint() ) {
			unset( $state['status'], $state['continue_url'] );
		}

		return new \WP_REST_Response(
			array_merge(
				array( 'ucp' => $this->ucp_metadata( 'success' ) ),
				( new CheckoutMapper() )->build_checkout( $cart ?? $this->cart_item_response(), $checkout_id, $state )
			),
			$http_status
		);
	}

	/**
	 * Build the Store API order response in the shape of a cart response, for the mappers to read.
	 *
	 * The order schema differs from the cart's where an order line is not a cart line: its ids,
	 * its name, and a price that is the product's current one rather than the one paid.
	 *
	 * @param \WC_Order $order The paid or on-hold order.
	 * @return array
	 */
	private function order_as_cart( \WC_Order $order ): array {
		$cart     = (array) json_decode( (string) wp_json_encode( $this->schema_controller->get( OrderSchema::IDENTIFIER )->get_item_response( $order ) ), true );
		$with_tax = 'incl' === get_option( 'woocommerce_tax_display_cart' );

		foreach ( array_values( $order->get_items() ) as $index => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || ! isset( $cart['items'][ $index ] ) ) {
				continue;
			}

			$line     = $cart['items'][ $index ];
			$product  = $item->get_product();
			$subtotal = (int) $line['totals']['line_subtotal'] + ( $with_tax ? (int) $line['totals']['line_subtotal_tax'] : 0 );

			$line['id']              = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$line['key']             = (string) $item->get_id();
			$line['prices']['price'] = (int) round( $subtotal / max( 1, (int) $item->get_quantity() ) );

			// An order line's name already holds the attributes the mapper appends from `variation`.
			if ( ! empty( $line['variation'] ) && $product instanceof \WC_Product ) {
				$line['name'] = $product->get_title();
			}

			$cart['items'][ $index ] = $line;
		}

		// A cart's `total_items` is the subtotal before discounts; an order's is the sum after them.
		$cart['totals']['total_items'] = $cart['totals']['subtotal'];
		$cart['errors']                = array();

		return $cart;
	}

	/**
	 * Replace the cart lines with the request's `line_items`, when it sends them.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|null An error response, or null when the lines were applied or left alone.
	 */
	protected function update_line_items( \WP_REST_Request $request ): ?\WP_REST_Response {
		$line_items = Utils::json_param( $request, 'line_items' );

		if ( null === $line_items ) {
			return null;
		}

		$desired = $this->parse_line_items( $line_items );

		return $desired instanceof \WP_REST_Response ? $desired : $this->apply_line_items( $desired );
	}

	/**
	 * Apply the request's `buyer`, `billing_address` and `shipping_address` to the session customer.
	 *
	 * Only the fields sent are changed. Each address is sanitized and validated by the Store API
	 * address schema, as `POST /cart/update-customer` does.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|null An error response, or null when the customer was updated or left alone.
	 */
	protected function update_customer( \WP_REST_Request $request ): ?\WP_REST_Response {
		$postal_fields = array(
			'first_name'       => 'first_name',
			'last_name'        => 'last_name',
			'street_address'   => 'address_1',
			'extended_address' => 'address_2',
			'address_locality' => 'city',
			'address_region'   => 'state',
			'postal_code'      => 'postcode',
			'address_country'  => 'country',
			'phone_number'     => 'phone',
		);

		$billing_address = $this->pick_fields( Utils::json_param( $request, 'billing_address' ), $postal_fields );
		$changes         = array(
			'billing'  => array_merge(
				$billing_address,
				$this->pick_fields(
					Utils::json_param( $request, 'buyer' ),
					array(
						'first_name'   => 'first_name',
						'last_name'    => 'last_name',
						'email'        => 'email',
						'phone_number' => 'phone',
					)
				)
			),
			'shipping' => $this->pick_fields( Utils::json_param( $request, 'shipping_address' ), $postal_fields ),
		);

		if ( empty( $changes['billing'] ) && empty( $changes['shipping'] ) ) {
			return null;
		}

		// As the Store API does: without shipping, taxes follow the billing address whatever the tax setting.
		if ( empty( $changes['shipping'] ) && ! $this->cart_controller->get_cart_instance()->needs_shipping() ) {
			$changes['shipping'] = $changes['billing'];
		}

		$customer = WC()->customer;
		$changes  = $this->share_between_addresses( $changes, $customer, empty( $billing_address ) );
		$props    = array();

		foreach ( $changes as $type => $fields ) {
			if ( empty( $fields ) ) {
				continue;
			}

			$address_schema = 'billing' === $type ? $this->cart_schema->billing_address_schema : $this->cart_schema->shipping_address_schema;
			$param          = $type . '_address';
			$address        = $address_schema->sanitize_callback( array_merge( $address_schema->get_item_response( $customer ), $fields ), $request, $param );
			$valid          = $address_schema->validate_callback( $address, $request, $param );

			if ( true !== $valid ) {
				$message = is_wp_error( $valid ) ? implode( ' ', $valid->get_error_messages() ) : __( 'Invalid address.', 'woocommerce' );

				return $this->ucp_error( 'VALIDATION_ERROR', $message, 'recoverable', 400, '$.' . $param );
			}

			foreach ( $address as $key => $value ) {
				$props[ $type . '_' . $key ] = $value;
			}
		}

		$customer->set_props( $props );
		$customer->save();
		$this->cart_controller->calculate_totals();

		return null;
	}

	/**
	 * Let one address supply what the other lacks, as the storefront's "same address for billing" does.
	 *
	 * Billing follows a shipping change while the two still match and the request names no billing
	 * address of its own. A name or phone held by only one address is copied to the other.
	 *
	 * @param array{billing: array<string, string>, shipping: array<string, string>} $changes Fields the request changes, per address.
	 * @param \WC_Customer                                                           $customer The session customer, before the changes.
	 * @param bool                                                                   $follow Whether billing may follow shipping.
	 * @return array{billing: array<string, string>, shipping: array<string, string>}
	 */
	private function share_between_addresses( array $changes, \WC_Customer $customer, bool $follow ): array {
		$billing  = $customer->get_billing();
		$shipping = $customer->get_shipping();
		$postal   = array_flip( array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) );

		if ( $follow && array_intersect_key( $billing, $postal ) === array_intersect_key( $shipping, $postal ) ) {
			$changes['billing'] = array_merge( array_intersect_key( $changes['shipping'], $postal ), $changes['billing'] );
		}

		$billing  = array_merge( $billing, $changes['billing'] );
		$shipping = array_merge( $shipping, $changes['shipping'] );

		foreach ( array( 'first_name', 'last_name', 'phone' ) as $key ) {
			if ( '' === (string) $billing[ $key ] && '' !== (string) $shipping[ $key ] ) {
				$changes['billing'][ $key ] = (string) $shipping[ $key ];
			} elseif ( '' === (string) $shipping[ $key ] && '' !== (string) $billing[ $key ] ) {
				$changes['shipping'][ $key ] = (string) $billing[ $key ];
			}
		}

		return $changes;
	}

	/**
	 * Copy the scalar fields of a request object into WooCommerce field names.
	 *
	 * @param mixed                 $input Request object, ignored unless an array.
	 * @param array<string, string> $map UCP field name to WooCommerce field name.
	 * @return array<string, string>
	 */
	private function pick_fields( $input, array $map ): array {
		$fields = array();

		if ( is_array( $input ) ) {
			foreach ( $map as $from => $to ) {
				if ( isset( $input[ $from ] ) && is_scalar( $input[ $from ] ) ) {
					$fields[ $to ] = (string) $input[ $from ];
				}
			}
		}

		return $fields;
	}
}
