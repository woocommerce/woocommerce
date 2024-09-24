<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidCartException;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\AdditionalFields;
use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * Checkout class.
 */
class Checkout extends AbstractCartRoute {
	use DraftOrderTrait;
	use CheckoutTrait;

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'checkout';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'checkout';

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
		return '/checkout';
	}

	/**
	 * Checks if a nonce is required for the route.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		return ! $this->has_cart_token( $request );
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array_merge(
					[
						'payment_data'      => [
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
						'customer_password' => [
							'description' => __( 'Customer password for new accounts, if applicable.', 'woocommerce' ),
							'type'        => 'string',
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
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$this->load_cart_session( $request );

		$response    = null;
		$nonce_check = $this->requires_nonce( $request ) ? $this->check_nonce( $request ) : null;

		if ( is_wp_error( $nonce_check ) ) {
			$response = $nonce_check;
		}

		if ( ! $response ) {
			try {
				$response = $this->get_response_by_request_method( $request );
			} catch ( InvalidCartException $error ) {
				$response = $this->get_route_error_response_from_object( $error->getError(), $error->getCode(), $error->getAdditionalData() );
			} catch ( RouteException $error ) {
				$response = $this->get_route_error_response( $error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData() );
			} catch ( \Exception $error ) {
				$response = $this->get_route_error_response( 'woocommerce_rest_unknown_server_error', $error->getMessage(), 500 );
			}
		}

		if ( is_wp_error( $response ) ) {
			$response = $this->error_to_response( $response );

			// If we encountered an exception, free up stock.
			if ( $this->order ) {
				wc_release_stock_for_order( $this->order );
			}
		}

		return $this->add_response_headers( $response );
	}

	/**
	 * Convert the cart into a new draft order, or update an existing draft order, and return an updated cart response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$this->create_or_update_draft_order( $request );

		return $this->prepare_item_for_response(
			(object) [
				'order'          => $this->order,
				'payment_result' => new PaymentResult(),
			],
			$request
		);
	}

	/**
	 * Validate required additional fields on request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @throws RouteException When a required additional field is missing.
	 */
	public function validate_required_additional_fields( \WP_REST_Request $request ) {
		$contact_fields           = $this->additional_fields_controller->get_fields_for_location( 'contact' );
		$order_fields             = $this->additional_fields_controller->get_fields_for_location( 'order' );
		$order_and_contact_fields = array_merge( $contact_fields, $order_fields );

		if ( ! empty( $order_and_contact_fields ) ) {
			foreach ( $order_and_contact_fields as $field_key => $order_and_contact_field ) {
				if ( $order_and_contact_field['required'] && ! isset( $request['additional_fields'][ $field_key ] ) ) {
					throw new RouteException(
						'woocommerce_rest_checkout_missing_required_field',
						/* translators: %s: is the field label */
						esc_html( sprintf( __( 'There was a problem with the provided additional fields: %s is required', 'woocommerce' ), $order_and_contact_field['label'] ) ),
						400
					);
				}
			}
		}

		$address_fields = $this->additional_fields_controller->get_fields_for_location( 'address' );
		if ( ! empty( $address_fields ) ) {
			$needs_shipping = WC()->cart->needs_shipping();
			foreach ( $address_fields as $field_key => $address_field ) {
				if ( $address_field['required'] && ! isset( $request['billing_address'][ $field_key ] ) ) {
					throw new RouteException(
						'woocommerce_rest_checkout_missing_required_field',
						/* translators: %s: is the field label */
						esc_html( sprintf( __( 'There was a problem with the provided billing address: %s is required', 'woocommerce' ), $address_field['label'] ) ),
						400
					);
				}
				if ( $needs_shipping && $address_field['required'] && ! isset( $request['shipping_address'][ $field_key ] ) ) {
					throw new RouteException(
						'woocommerce_rest_checkout_missing_required_field',
						/* translators: %s: is the field label */
						esc_html( sprintf( __( 'There was a problem with the provided shipping address: %s is required', 'woocommerce' ), $address_field['label'] ) ),
						400
					);
				}
			}
		}
	}

	/**
	 * Process an order.
	 *
	 * 1. Obtain Draft Order
	 * 2. Process Request
	 * 3. Process Customer
	 * 4. Validate Order
	 * 5. Process Payment
	 *
	 * @throws RouteException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		/**
		 * Before triggering validation, ensure totals are current and in turn, things such as shipping costs are present.
		 * This is so plugins that validate other cart data (e.g. conditional shipping and payments) can access this data.
		 */
		$this->cart_controller->calculate_totals();

		/**
		 * Validate items and fix violations before the order is processed.
		 */
		$this->cart_controller->validate_cart();

		/**
		 * Validate additional fields on request.
		 */
		$this->validate_required_additional_fields( $request );

		/**
		 * Persist customer session data from the request first so that OrderController::update_addresses_from_cart
		 * uses the up to date customer address.
		 */
		$this->update_customer_from_request( $request );

		/**
		 * Create (or update) Draft Order and process request data.
		 */
		$this->create_or_update_draft_order( $request );
		$this->update_order_from_request( $request );
		$this->process_customer( $request );

		/**
		 * Validate updated order before payment is attempted.
		 */
		$this->order_controller->validate_order_before_payment( $this->order );

		/**
		 * Reserve stock for the order.
		 *
		 * In the shortcode based checkout, when POSTing the checkout form the order would be created and fire the
		 * `woocommerce_checkout_order_created` action. This in turn would trigger the `wc_reserve_stock_for_order`
		 * function so that stock would be held pending payment.
		 *
		 * Via the block based checkout and Store API we already have a draft order, but when POSTing to the /checkout
		 * endpoint we do the same; reserve stock for the order to allow time to process payment.
		 *
		 * Note, stock is only "held" while the order has the status wc-checkout-draft or pending. Stock is freed when
		 * the order changes status, or there is an exception.
		 *
		 * @see ReserveStock::get_query_for_reserved_stock()
		 *
		 * @since 9.2 Stock is no longer held for all draft orders, nor on non-POST requests. See https://github.com/woocommerce/woocommerce/issues/44231
		 * @since 9.2 Uses wc_reserve_stock_for_order() instead of using the ReserveStock class directly.
		 */
		try {
			wc_reserve_stock_for_order( $this->order );
		} catch ( ReserveStockException $e ) {
			throw new RouteException(
				esc_html( $e->getErrorCode() ),
				esc_html( $e->getMessage() ),
				esc_html( $e->getCode() )
			);
		}

		wc_do_deprecated_action(
			'__experimental_woocommerce_blocks_checkout_order_processed',
			array(
				$this->order,
			),
			'6.3.0',
			'woocommerce_store_api_checkout_order_processed',
			'This action was deprecated in WooCommerce Blocks version 6.3.0. Please use woocommerce_store_api_checkout_order_processed instead.'
		);

		wc_do_deprecated_action(
			'woocommerce_blocks_checkout_order_processed',
			array(
				$this->order,
			),
			'7.2.0',
			'woocommerce_store_api_checkout_order_processed',
			'This action was deprecated in WooCommerce Blocks version 7.2.0. Please use woocommerce_store_api_checkout_order_processed instead.'
		);

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
		 * @example See docs/examples/checkout-order-processed.md

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
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 500.
	 * @param array  $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = 500, $additional_data = [] ) {
		$error_from_message = new \WP_Error(
			$error_code,
			$error_message
		);
		// 409 is when there was a conflict, so we return the cart so the client can resolve it.
		if ( 409 === $http_status_code ) {
			return $this->add_data_to_error_object( $error_from_message, $additional_data, $http_status_code, true );
		}
		return $this->add_data_to_error_object( $error_from_message, $additional_data, $http_status_code );
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param \WP_Error $error_object User facing error message.
	 * @param int       $http_status_code HTTP status. Defaults to 500.
	 * @param array     $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response_from_object( $error_object, $http_status_code = 500, $additional_data = [] ) {
		// 409 is when there was a conflict, so we return the cart so the client can resolve it.
		if ( 409 === $http_status_code ) {
			return $this->add_data_to_error_object( $error_object, $additional_data, $http_status_code, true );
		}
		return $this->add_data_to_error_object( $error_object, $additional_data, $http_status_code );
	}

	/**
	 * Adds additional data to the \WP_Error object.
	 *
	 * @param \WP_Error $error The error object to add the cart to.
	 * @param array     $data The data to add to the error object.
	 * @param int       $http_status_code The HTTP status code this error should return.
	 * @param bool      $include_cart Whether the cart should be included in the error data.
	 * @returns \WP_Error The \WP_Error with the cart added.
	 */
	private function add_data_to_error_object( $error, $data, $http_status_code, bool $include_cart = false ) {
		$data = array_merge( $data, [ 'status' => $http_status_code ] );
		if ( $include_cart ) {
			$data = array_merge( $data, [ 'cart' => wc_get_container()->get( RestApiUtil::class )->get_endpoint_data( '/wc/store/v1/cart' ) ] );
		}
		$error->add_data( $data );
		return $error;
	}

	/**
	 * Create or update a draft order based on the cart.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @throws RouteException On error.
	 */
	private function create_or_update_draft_order( \WP_REST_Request $request ) {
		$this->order = $this->get_draft_order();

		if ( ! $this->order ) {
			$this->order = $this->order_controller->create_order_from_cart();
		} else {
			$this->order_controller->update_order_from_cart( $this->order, true );
		}

		wc_do_deprecated_action(
			'__experimental_woocommerce_blocks_checkout_update_order_meta',
			array(
				$this->order,
			),
			'6.3.0',
			'woocommerce_store_api_checkout_update_order_meta',
			'This action was deprecated in WooCommerce Blocks version 6.3.0. Please use woocommerce_store_api_checkout_update_order_meta instead.'
		);

		wc_do_deprecated_action(
			'woocommerce_blocks_checkout_update_order_meta',
			array(
				$this->order,
			),
			'7.2.0',
			'woocommerce_store_api_checkout_update_order_meta',
			'This action was deprecated in WooCommerce Blocks version 7.2.0. Please use woocommerce_store_api_checkout_update_order_meta instead.'
		);

		/**
		 * Fires when the Checkout Block/Store API updates an order's meta data.
		 *
		 * This hook gives extensions the chance to add or update meta data on the $order.
		 * Throwing an exception from a callback attached to this action will make the Checkout Block render in a warning state, effectively preventing checkout.
		 *
		 * This is similar to existing core hook woocommerce_checkout_update_order_meta.
		 * We're using a new action:
		 * - To keep the interface focused (only pass $order, not passing request data).
		 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
		 *
		 * @since 7.2.0
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686
		 *
		 * @param \WC_Order $order Order object.
		 */
		do_action( 'woocommerce_store_api_checkout_update_order_meta', $this->order );

		// Confirm order is valid before proceeding further.
		if ( ! $this->order instanceof \WC_Order ) {
			throw new RouteException(
				'woocommerce_rest_checkout_missing_order',
				__( 'Unable to create order', 'woocommerce' ),
				500
			);
		}

		// Store order ID to session.
		$this->set_draft_order_id( $this->order->get_id() );
	}

	/**
	 * Updates the current customer session using data from the request (e.g. address data).
	 *
	 * Address session data is synced to the order itself later on by OrderController::update_order_from_cart()
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_customer_from_request( \WP_REST_Request $request ) {
		$customer = wc()->customer;

		// Billing address is a required field.
		foreach ( $request['billing_address'] as $key => $value ) {
			$callback = "set_billing_$key";
			if ( is_callable( [ $customer, $callback ] ) ) {
				$customer->$callback( $value );
			} elseif ( $this->additional_fields_controller->is_field( $key ) ) {
				$this->additional_fields_controller->persist_field_for_customer( $key, $value, $customer, 'billing' );
			}
		}

		// If shipping address (optional field) was not provided, set it to the given billing address (required field).
		$shipping_address_values = $request['shipping_address'] ?? $request['billing_address'];

		foreach ( $shipping_address_values as $key => $value ) {
			$callback = "set_shipping_$key";
			if ( is_callable( [ $customer, $callback ] ) ) {
				$customer->$callback( $value );
			} elseif ( $this->additional_fields_controller->is_field( $key ) ) {
				$this->additional_fields_controller->persist_field_for_customer( $key, $value, $customer, 'shipping' );
			}
		}

		// Persist contact fields to session.
		$contact_fields = $this->additional_fields_controller->get_contact_fields_keys();

		if ( ! empty( $contact_fields ) ) {
			foreach ( $contact_fields as $key ) {
				if ( isset( $request['additional_fields'], $request['additional_fields'][ $key ] ) ) {
					$this->additional_fields_controller->persist_field_for_customer( $key, $request['additional_fields'][ $key ], $customer );
				}
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
	}

	/**
	 * Gets the chosen payment method from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Payment_Gateway|null
	 */
	private function get_request_payment_method( \WP_REST_Request $request ) {
		$available_gateways      = WC()->payment_gateways->get_available_payment_gateways();
		$request_payment_method  = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );
		$requires_payment_method = $this->order->needs_payment();

		if ( empty( $request_payment_method ) ) {
			if ( $requires_payment_method ) {
				throw new RouteException(
					'woocommerce_rest_checkout_missing_payment_method',
					__( 'No payment method provided.', 'woocommerce' ),
					400
				);
			}
			return null;
		}

		if ( ! isset( $available_gateways[ $request_payment_method ] ) ) {
			$all_payment_gateways = WC()->payment_gateways->payment_gateways();
			$gateway_title        = isset( $all_payment_gateways[ $request_payment_method ] ) ? $all_payment_gateways[ $request_payment_method ]->get_title() : $request_payment_method;
			throw new RouteException(
				'woocommerce_rest_checkout_payment_method_disabled',
				sprintf(
					// Translators: %s Payment method ID.
					__( '%s is not available for this order—please choose a different payment method', 'woocommerce' ),
					esc_html( $gateway_title )
				),
				400
			);
		}

		return $available_gateways[ $request_payment_method ];
	}

	/**
	 * Order processing relating to customer account.
	 *
	 * Creates a customer account as needed (based on request & store settings) and  updates the order with the new customer ID.
	 * Updates the order with user details (e.g. address).
	 *
	 * @throws RouteException API error object with error details.
	 * @param \WP_REST_Request $request Request object.
	 */
	private function process_customer( \WP_REST_Request $request ) {
		try {
			if ( $this->should_create_customer_account( $request ) ) {
				$customer_id = $this->create_customer_account(
					$request['billing_address']['email'],
					$request['billing_address']['first_name'],
					$request['billing_address']['last_name'],
					$request['customer_password']
				);

				// Associate customer with the order. This is done before login to ensure the order is associated with
				// the correct customer if login fails.
				$this->order->set_customer_id( $customer_id );
				$this->order->save();

				// Log the customer in to WordPress. Doing this inline instead of using `wc_set_customer_auth_cookie`
				// because wc_set_customer_auth_cookie forces the use of session cookie.
				wp_set_current_user( $customer_id );
				wp_set_auth_cookie( $customer_id, true );

				// Init session cookie if the session cookie handler exists.
				if ( is_callable( [ WC()->session, 'init_session_cookie' ] ) ) {
					WC()->session->init_session_cookie();
				}
			}
		} catch ( \Exception $error ) {
			switch ( $error->getMessage() ) {
				case 'registration-error-invalid-email':
					throw new RouteException(
						'registration-error-invalid-email',
						esc_html__( 'Please provide a valid email address.', 'woocommerce' ),
						400
					);
				case 'registration-error-email-exists':
					throw new RouteException(
						'registration-error-email-exists',
						sprintf(
							// Translators: %s Email address.
							esc_html__( 'An account is already registered with %s. Please log in or use a different email address.', 'woocommerce' ),
							esc_html( $request['billing_address']['email'] )
						),
						400
					);
				case 'registration-error-empty-password':
					throw new RouteException(
						'registration-error-empty-password',
						esc_html__( 'Please create a password for your account.', 'woocommerce' ),
						400
					);
			}
		}

		// Persist customer address data to account.
		$this->order_controller->sync_customer_data_with_order( $this->order );
	}

	/**
	 * Check request options and store (shop) config to determine if a user account should be created as part of order
	 * processing.
	 *
	 * @param \WP_REST_Request $request The current request object being handled.
	 * @return boolean True if a new user account should be created.
	 */
	private function should_create_customer_account( \WP_REST_Request $request ) {
		if ( is_user_logged_in() ) {
			return false;
		}

		// Return false if registration is not enabled for the store.
		if ( false === filter_var( wc()->checkout()->is_registration_enabled(), FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		// Return true if the store requires an account for all purchases. Note - checkbox is not displayed to shopper in this case.
		if ( true === filter_var( wc()->checkout()->is_registration_required(), FILTER_VALIDATE_BOOLEAN ) ) {
			return true;
		}

		// Create an account if requested via the endpoint.
		if ( true === filter_var( $request['create_account'], FILTER_VALIDATE_BOOLEAN ) ) {
			// User has requested an account as part of checkout processing.
			return true;
		}

		return false;
	}

	/**
	 * Create a new account for a customer.
	 *
	 * The account is created with a generated username. The customer is sent
	 * an email notifying them about the account and containing a link to set
	 * their (initial) password.
	 *
	 * Intended as a replacement for wc_create_new_customer in WC core.
	 *
	 * @throws \Exception If an error is encountered when creating the user account.
	 *
	 * @param string $user_email The email address to use for the new account.
	 * @param string $first_name The first name to use for the new account.
	 * @param string $last_name  The last name to use for the new account.
	 * @param string $password   The password to use for the new account. If empty, a password will be generated.
	 *
	 * @return int User id if successful
	 */
	private function create_customer_account( $user_email, $first_name, $last_name, $password = '' ) {
		if ( empty( $user_email ) || ! is_email( $user_email ) ) {
			throw new \Exception( 'registration-error-invalid-email' );
		}

		if ( email_exists( $user_email ) ) {
			throw new \Exception( 'registration-error-email-exists' );
		}

		// Handle password creation if not provided.
		if ( empty( $password ) ) {
			$password           = wp_generate_password();
			$password_generated = true;
		} else {
			$password_generated = false;
		}

		// This ensures `wp_generate_password` returned something (it is filterable and could be empty string).
		if ( empty( $password ) ) {
			throw new \Exception( 'registration-error-empty-password' );
		}

		$username = wc_create_new_customer_username( $user_email );

		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		/**
		 * Fires before a customer account is registered.
		 *
		 * This hook fires before customer accounts are created and passes the form data (username, email) and an array
		 * of errors.
		 *
		 * This could be used to add extra validation logic and append errors to the array.
		 *
		 * @since 7.2.0
		 *
		 * @internal Matches filter name in WooCommerce core.
		 *
		 * @param string $username Customer username.
		 * @param string $user_email Customer email address.
		 * @param \WP_Error $errors Error object.
		 */
		do_action( 'woocommerce_register_post', $username, $user_email, $errors );

		/**
		 * Filters registration errors before a customer account is registered.
		 *
		 * This hook filters registration errors. This can be used to manipulate the array of errors before
		 * they are displayed.
		 *
		 * @since 7.2.0
		 *
		 * @internal Matches filter name in WooCommerce core.
		 *
		 * @param \WP_Error $errors Error object.
		 * @param string $username Customer username.
		 * @param string $user_email Customer email address.
		 * @return \WP_Error
		 */
		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $user_email );

		if ( is_wp_error( $errors ) && $errors->get_error_code() ) {
			throw new \Exception( $errors->get_error_code() );
		}

		/**
		 * Filters customer data before a customer account is registered.
		 *
		 * This hook filters customer data. It allows user data to be changed, for example, username, password, email,
		 * first name, last name, and role.
		 *
		 * @since 7.2.0
		 *
		 * @param array $customer_data An array of customer (user) data.
		 * @return array
		 */
		$new_customer_data = apply_filters(
			'woocommerce_new_customer_data',
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $user_email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'role'       => 'customer',
				'source'     => 'store-api',
			)
		);

		$customer_id = wp_insert_user( $new_customer_data );

		if ( is_wp_error( $customer_id ) ) {
			throw $this->map_create_account_error( $customer_id );
		}

		// Set account flag to remind customer to update generated password.
		update_user_option( $customer_id, 'default_password_nag', true, true );

		/**
		 * Fires after a customer account has been registered.
		 *
		 * This hook fires after customer accounts are created and passes the customer data.
		 *
		 * @since 7.2.0
		 *
		 * @internal Matches filter name in WooCommerce core.
		 *
		 * @param integer $customer_id New customer (user) ID.
		 * @param array $new_customer_data Array of customer (user) data.
		 * @param string $password_generated The generated password for the account.
		 */
		do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

		return $customer_id;
	}

	/**
	 * Convert an account creation error to an exception.
	 *
	 * @param \WP_Error $error An error object.
	 * @return \Exception.
	 */
	private function map_create_account_error( \WP_Error $error ) {
		switch ( $error->get_error_code() ) {
			// WordPress core error codes.
			case 'empty_username':
			case 'invalid_username':
			case 'empty_email':
			case 'invalid_email':
			case 'email_exists':
			case 'registerfail':
				return new \Exception( 'woocommerce_rest_checkout_create_account_failure' );
		}
		return new \Exception( 'woocommerce_rest_checkout_create_account_failure' );
	}
}
