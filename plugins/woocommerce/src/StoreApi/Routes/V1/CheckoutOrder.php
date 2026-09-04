<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidStockLevelsInCartException;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\OrderAuthorizationTrait;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\StoreApi\Utilities\ValidationUtils;

/**
 * CheckoutOrder class.
 */
class CheckoutOrder extends AbstractCartRoute {
	use OrderAuthorizationTrait;
	use CheckoutTrait;

	/**
	 * Address fields an order's pricing is matched on. Shipping zones use country, state and
	 * postcode, tax rates also use city; city is compared for both so the checks stay consistent.
	 *
	 * @see \WC_Shipping_Zones::get_zone_matching_package()
	 * @see \WC_Tax::find_rates()
	 *
	 * @var string[]
	 */
	private const PRICING_ADDRESS_FIELDS = [ 'country', 'state', 'postcode', 'city' ];

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'checkout-order';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'checkout-order';

	/**
	 * Holds the current order being processed.
	 *
	 * @var \WC_Order
	 */
	private $order = null;

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout/(?P<id>[\d]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => array_merge(
					[
						'payment_data' => [
							'description' => __( 'Data to pass through to the payment method when processing payment.', 'woocommerce' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'key'   => [
										'type' => 'string',
									],
									'value' => [
										'type' => [ 'string', 'boolean' ],
									],
								],
							],
						],
					],
					$this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE )
				),
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Process an order.
	 *
	 * 1. Process Request
	 * 2. Process Customer
	 * 3. Validate Order
	 * 4. Process Payment
	 *
	 * @throws RouteException On error.
	 * @throws InvalidStockLevelsInCartException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$order_id    = absint( $request['id'] );
		$this->order = wc_get_order( $order_id );

		if ( ! $this->order instanceof \WC_Order || ! $this->order->needs_payment() ) {
			return new \WP_Error(
				'invalid_order_update_status',
				__( 'This order cannot be paid for.', 'woocommerce' )
			);
		}

		/**
		 * Process request data.
		 *
		 * The order address is validated before anything is persisted, so a rejected request
		 * cannot mutate the existing order or the customer record.
		 */
		$this->update_billing_address( $request );
		$this->update_order_from_request( $request );

		/**
		 * Process customer data.
		 *
		 * Update order with customer details, and sign up a user account as necessary.
		 */
		$this->process_customer( $request );

		/**
		 * Validate order.
		 *
		 * This logic ensures the order is valid before payment is attempted.
		 */
		$this->order_controller->validate_existing_order_before_payment( $this->order );

		/**
		 * Fires before an order is processed by the Checkout Block/Store API.
		 *
		 * This hook informs extensions that $order has completed processing and is ready for payment.
		 *
		 * This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:
		 * - To keep the interface focused (only pass $order, not passing request data).
		 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
		 *
		 * @since 7.2.0
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238
		 * @example docs/examples/checkout-order-processed.md

		 * @param \WC_Order $order Order object.
		 */
		do_action( 'woocommerce_store_api_checkout_order_processed', $this->order );

		/**
		 * Process the payment and return the results.
		 */
		$payment_result = new PaymentResult();

		if ( $this->order->needs_payment() ) {
			$this->process_payment( $request, $payment_result );
		} else {
			$this->process_without_payment( $request, $payment_result );
		}

		return $this->prepare_item_for_response(
			(object) [
				'order'          => wc_get_order( $this->order ),
				'payment_result' => $payment_result,
			],
			$request
		);
	}

	/**
	 * Since this endpoint only operates on existing orders, we don't need to do updates based on
	 * the cart data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function cart_updated( \WP_REST_Request $request ) {}

	/**
	 * Applies the billing and shipping address from the request to the order and customer.
	 *
	 * The address is set on the order and validated before anything is persisted, so a rejected
	 * request cannot mutate the order or the customer. wc()->customer is saved on shutdown (see
	 * WooCommerce::initialize_cart()), so its address fields are only set once validation passes.
	 *
	 * @throws RouteException When the order address fails validation.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_billing_address( \WP_REST_Request $request ) {
		$customer = wc()->customer;

		// Billing address is a required field.
		$billing = $request['billing_address'];

		// Shipping is optional. Keep the address the order already holds so a billing-only request does not
		// re-address it, and fall back to billing when it never had one (set_shipping_address() takes an array).
		$fallback_shipping = '' !== $this->order->get_shipping_country() ? $this->order->get_address( 'shipping' ) : $billing;
		$shipping          = $request['shipping_address'] ?? $fallback_shipping;

		// Captured before the request is applied so the guard below compares against the order as priced.
		$priced_destination      = $this->get_shipping_destination();
		$priced_tax_location     = $this->order->get_taxable_location();
		$was_priced_with_address = '' !== $this->order->get_billing_country() || '' !== $priced_destination['country'];

		$this->order->set_billing_address( $billing );
		$this->order->set_shipping_address( $shipping );
		$this->order_controller->validate_existing_order_before_update( $this->order );
		$this->validate_order_is_still_priced( $priced_destination, $priced_tax_location, $was_priced_with_address );

		// Update customer object with validated order addresses.
		foreach ( $billing as $key => $value ) {
			if ( is_callable( [ $customer, "set_billing_$key" ] ) ) {
				$customer->{"set_billing_$key"}( $value );
			}
		}

		foreach ( $shipping as $key => $value ) {
			if ( is_callable( [ $customer, "set_shipping_$key" ] ) ) {
				$customer->{"set_shipping_$key"}( $value );
			}
		}

		/**
		 * Fires when the Checkout Block/Store API updates a customer from the API request data.
		 *
		 * @since 8.2.0
		 *
		 * @param \WC_Customer $customer Customer object.
		 * @param \WP_REST_Request $request Full details about the request.
		 */
		do_action( 'woocommerce_store_api_checkout_update_customer_from_request', $customer, $request );

		$customer->save();
		$this->order->save();
		$this->order->calculate_totals();
	}

	/**
	 * Reads the destination the order is priced to ship to.
	 *
	 * @return array
	 */
	private function get_shipping_destination(): array {
		return [
			'country'  => $this->order->get_shipping_country(),
			'state'    => $this->order->get_shipping_state(),
			'postcode' => $this->order->get_shipping_postcode(),
			'city'     => $this->order->get_shipping_city(),
		];
	}

	/**
	 * Rejects an address change that would alter what the order costs.
	 *
	 * @throws RouteException When the order would have to be re-priced.
	 *
	 * @param array $priced_destination  Shipping destination the order is priced against.
	 * @param array $priced_tax_location Tax location the order is priced against.
	 * @param bool  $was_priced_with_address Whether the order held an address before the request was applied.
	 */
	private function validate_order_is_still_priced( array $priced_destination, array $priced_tax_location, bool $was_priced_with_address ): void {
		// An order with no address was never priced against one, e.g. a merchant-created order the shopper
		// is addressing for the first time, so there is nothing to protect.
		if ( ! $was_priced_with_address ) {
			return;
		}

		// needs_shipping() hydrates a product per line item, so let the free comparisons short-circuit it.
		if ( '' !== $priced_destination['country']
			&& $this->pricing_fields_differ( self::PRICING_ADDRESS_FIELDS, $priced_destination, $this->get_shipping_destination() )
			&& $this->order->needs_shipping() ) {
			throw new RouteException(
				'woocommerce_rest_checkout_order_address_change_not_allowed',
				esc_html__( 'Sorry, the shipping address on this order cannot be changed because the shipping cost was calculated for the original address. Please use the original address, or contact us to have the order updated.', 'woocommerce' ),
				400
			);
		}

		// Resolved through the order so this follows whichever address actually prices it, including the
		// shop base for local pickup and anything woocommerce_order_get_tax_location redirects it to.
		// A store that collects no tax has no tax location to protect.
		if ( wc_tax_enabled() && $this->pricing_fields_differ( self::PRICING_ADDRESS_FIELDS, $priced_tax_location, $this->order->get_taxable_location() ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_order_address_change_not_allowed',
				esc_html__( 'Sorry, the address on this order cannot be changed because the tax was calculated for the original address. Please use the original address, or contact us to have the order updated.', 'woocommerce' ),
				400
			);
		}
	}

	/**
	 * Compares two sets of pricing fields.
	 *
	 * Values are normalized the way WooCommerce keys pricing on them, so a value that differs only in case,
	 * postcode spacing, or state spelling resolves to the same zone and tax rate and is not a difference.
	 *
	 * @param string[] $fields  Fields to compare.
	 * @param array    $priced  Values the order is priced against.
	 * @param array    $updated Values the request would leave on the order.
	 * @return bool
	 */
	private function pricing_fields_differ( array $fields, array $priced, array $updated ): bool {
		foreach ( $fields as $field ) {
			$priced_value = $this->normalize_pricing_address_field( $field, $priced );

			if ( $priced_value !== $this->normalize_pricing_address_field( $field, $updated ) ) {
				return true;
			}

			// State is the one field normalized past what the lookups do, so an order holding a state name was
			// priced against a value that matched no rate. Its code would compare equal and hide the re-price.
			if ( 'state' === $field && strtoupper( trim( (string) ( $priced[ $field ] ?? '' ) ) ) !== $priced_value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes an address field to the form shipping zones and tax rates are matched on.
	 *
	 * @param string $field   Field name.
	 * @param array  $address Address the field belongs to, used to resolve a state against its country.
	 * @return string
	 */
	private function normalize_pricing_address_field( string $field, array $address ): string {
		$value = (string) ( $address[ $field ] ?? '' );

		if ( 'postcode' === $field ) {
			return wc_normalize_postcode( $value );
		}

		// A state reaches the order as a name or a code depending on how it was created; the code is the form
		// a rate is registered under, so compare on it and let pricing_fields_differ() handle a stored name.
		if ( 'state' === $field ) {
			$value = ( new ValidationUtils() )->format_state( $value, (string) ( $address['country'] ?? '' ) );
		}

		// Both lookups uppercase with strtoupper(), which leaves multibyte characters alone. Matching that
		// keeps the guard from treating two values as equal when the zone or tax lookup would not.
		return strtoupper( trim( $value ) );
	}

	/**
	 * Gets the chosen payment method from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Payment_Gateway|null
	 */
	private function get_request_payment_method( \WP_REST_Request $request ) {
		$request_payment_method = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );

		if ( empty( $request_payment_method ) ) {
			if ( $this->order->needs_payment() ) {
				throw new RouteException(
					'woocommerce_rest_checkout_missing_payment_method',
					__( 'No payment method provided.', 'woocommerce' ),
					400
				);
			}
			return null;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( ! isset( $available_gateways[ $request_payment_method ] ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_payment_method_disabled',
				sprintf(
					// Translators: %s Payment method ID.
					__( 'The %s payment gateway is not available.', 'woocommerce' ),
					esc_html( $request_payment_method )
				),
				400
			);
		}

		return $available_gateways[ $request_payment_method ];
	}

	/**
	 * Updates the order with user details (e.g. address).
	 *
	 * @throws RouteException API error object with error details.
	 * @param \WP_REST_Request $request Request object.
	 */
	private function process_customer( \WP_REST_Request $request ) {
		$this->order_controller->sync_customer_data_with_order( $this->order );
	}
}
