<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Enums\OrderStatus;
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
	 * Session key holding the checkout state: `created_at`, and once set `status`, `continue_url` and `order_id`.
	 *
	 * Its presence is what makes a cart session a checkout.
	 */
	const CHECKOUT_SESSION_KEY = 'ucp_checkout';

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
	 * @param bool $for_update Whether the request changes the checkout, which a completed or canceled one refuses.
	 * @return array<string, mixed>|\WP_REST_Response The state, or the 404/409 error response.
	 */
	protected function open_checkout( bool $for_update ) {
		$state = $this->checkout_state();

		if ( null === $state ) {
			return $this->ucp_not_found();
		}

		if ( $for_update && $this->is_terminal( $state ) ) {
			return $this->ucp_conflict();
		}

		return $state;
	}

	/**
	 * The bound session's checkout state, with the order filled in once the buyer has paid it.
	 *
	 * Paying happens in the browser, outside this session, so `completed` is read off the
	 * order rather than stored. An order that can no longer be paid, such as one the hold-stock
	 * timer cancelled, drops the hand-off so the status derives again.
	 *
	 * @return array<string, mixed>|null Null when the session holds no checkout.
	 */
	protected function checkout_state(): ?array {
		$state = WC()->session ? WC()->session->get( self::CHECKOUT_SESSION_KEY ) : null;

		if ( ! is_array( $state ) ) {
			return null;
		}

		$order = empty( $state['order_id'] ) ? null : wc_get_order( (int) $state['order_id'] );

		if ( $order instanceof \WC_Order && $order->is_paid() ) {
			$state['status'] = CheckoutMapper::STATUS_COMPLETED;
			$state['order']  = array(
				'id'            => (string) $order->get_id(),
				'permalink_url' => $order->get_checkout_order_received_url(),
			);
		} elseif ( ! empty( $state['order_id'] ) && ! ( $order instanceof \WC_Order && $order->has_status( array( OrderStatus::PENDING, OrderStatus::FAILED, OrderStatus::ON_HOLD ) ) ) ) {
			// Status list rather than needs_payment(): that is false for a zero-total order, and
			// on-hold is a payment the buyer made that the store has yet to confirm.
			unset( $state['status'], $state['continue_url'], $state['order_id'] );
		}

		return $state;
	}

	/**
	 * Cancel the order handed to the buyer while its payment page would still take it, and forget it.
	 *
	 * Once the checkout changes or ends, that order no longer matches it and must not be paid.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 * @param string               $note Order note explaining the cancellation.
	 * @return array<string, mixed> The state without `order_id`.
	 */
	protected function cancel_handed_off_order( array $state, string $note ): array {
		$order = empty( $state['order_id'] ) ? null : wc_get_order( (int) $state['order_id'] );

		if ( $order instanceof \WC_Order ) {
			// The payment page's own list, so a failed attempt the buyer could retry is closed too.
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook, documented in WC_Order::needs_payment().
			$payable = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( OrderStatus::PENDING, OrderStatus::FAILED ), $order );

			if ( $order->has_status( $payable ) ) {
				$order->update_status( OrderStatus::CANCELLED, $note );

				if ( (int) $this->get_draft_order_id() === $order->get_id() ) {
					$this->set_draft_order_id( 0 );
				}
			}
		}

		unset( $state['order_id'] );

		return $state;
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
	 * Whether a checkout is completed or canceled.
	 *
	 * @param array<string, mixed> $state Checkout state.
	 * @return bool
	 */
	protected function is_terminal( array $state ): bool {
		return in_array( $state['status'] ?? '', array( CheckoutMapper::STATUS_COMPLETED, CheckoutMapper::STATUS_CANCELED ), true );
	}

	/**
	 * Build the response refusing a change to a completed or canceled checkout.
	 *
	 * @return \WP_REST_Response
	 */
	protected function ucp_conflict(): \WP_REST_Response {
		return $this->ucp_error( 'CONFLICT', __( 'This checkout session is already completed or canceled.', 'woocommerce' ), 'unrecoverable', 409, '$.status' );
	}

	/**
	 * Build a successful checkout response.
	 *
	 * @param string               $checkout_id Checkout identifier.
	 * @param array<string, mixed> $state Checkout state.
	 * @param int                  $http_status HTTP status.
	 * @param array|null           $cart Store API cart response already built for this request, or null to build it.
	 * @return \WP_REST_Response
	 */
	protected function checkout_response( string $checkout_id, array $state, int $http_status = 200, ?array $cart = null ): \WP_REST_Response {
		return new \WP_REST_Response(
			array_merge(
				array( 'ucp' => $this->ucp_metadata( 'success' ) ),
				( new CheckoutMapper() )->build_checkout( $cart ?? $this->cart_item_response(), $checkout_id, $state )
			),
			$http_status
		);
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

		$changes = array(
			'billing'  => array_merge(
				$this->pick_fields( Utils::json_param( $request, 'billing_address' ), $postal_fields ),
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
