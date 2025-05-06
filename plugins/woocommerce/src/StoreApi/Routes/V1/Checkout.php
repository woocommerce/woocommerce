<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidCartException;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Admin\Features\Features;

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
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array_merge(
					[
						'additional_fields' => [
							'description' => __( 'Additional fields related to the order.', 'woocommerce' ),
							'type'        => 'object',
						],
						'payment_method'    => [
							'description' => __( 'Selected payment method for the order.', 'woocommerce' ),
							'type'        => 'string',
						],
						'order_notes'       => [
							'description' => __( 'Order notes.', 'woocommerce' ),
							'type'        => 'string',
						],
					],
					$this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE )
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

			// If we encountered an exception, free up stock and release held coupons.
			if ( $this->order ) {
				wc_release_stock_for_order( $this->order );
				wc_release_coupons_for_order( $this->order );
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
	 * Validation callback for the checkout route.
	 *
	 * This runs after individual field validation_callbacks have been called.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function validate_callback( $request ) {
		$validate_contexts = [
			'shipping_address' => [
				'group'    => 'shipping',
				'location' => 'address',
				'param'    => 'shipping_address',
			],
			'billing_address'  => [
				'group'    => 'billing',
				'location' => 'address',
				'param'    => 'billing_address',
			],
			'contact'          => [
				'group'    => 'other',
				'location' => 'contact',
				'param'    => 'additional_fields',
			],
			'order'            => [
				'group'    => 'other',
				'location' => 'order',
				'param'    => 'additional_fields',
			],
		];

		if ( ! WC()->cart->needs_shipping() ) {
			unset( $validate_contexts['shipping_address'] );
		}

		$invalid_groups  = [];
		$invalid_details = [];
		$is_partial      = in_array( $request->get_method(), [ 'PUT', 'PATCH' ], true );

		foreach ( $validate_contexts as $context => $context_data ) {
			$errors = new \WP_Error();

			if ( Features::is_enabled( 'experimental-blocks' ) ) {
				$document_object = $this->get_document_object_from_rest_request( $request );
				$document_object->set_context( $context );
				$additional_fields = $this->additional_fields_controller->get_contextual_fields_for_location( $context_data['location'], $document_object );
			} else {
				$additional_fields = $this->additional_fields_controller->get_fields_for_location( $context_data['location'] );
			}

			// These values are used to validate custom rules and generate the document object.
			$field_values = (array) $request->get_param( $context_data['param'] ) ?? [];

			foreach ( $additional_fields as $field_key => $field ) {
				// Skip values that were not posted if the request is partial or the field is not required.
				if ( ! isset( $field_values[ $field_key ] ) && ( $is_partial || empty( $field['required'] ) ) ) {
					continue;
				}

				// Clean the field value to trim whitespace.
				$field_value = wc_clean( wp_unslash( $field_values[ $field_key ] ?? '' ) );

				if ( empty( $field_value ) ) {
					if ( ! empty( $field['required'] ) ) {
						/* translators: %s: is the field label */
						$error_message = sprintf( __( '%s is required', 'woocommerce' ), $field['label'] );
						if ( 'shipping_address' === $context ) {
							/* translators: %s: is the field error message */
							$error_message = sprintf( __( 'There was a problem with the provided shipping address: %s', 'woocommerce' ), $error_message );
						} elseif ( 'billing_address' === $context ) {
							/* translators: %s: is the field error message */
							$error_message = sprintf( __( 'There was a problem with the provided billing address: %s', 'woocommerce' ), $error_message );
						}
						$errors->add( 'woocommerce_required_checkout_field', $error_message, [ 'key' => $field_key ] );
					}
					continue;
				}

				$valid_check = $this->additional_fields_controller->validate_field( $field, $field_value );

				if ( is_wp_error( $valid_check ) && $valid_check->has_errors() ) {
					foreach ( $valid_check->get_error_codes() as $code ) {
						$valid_check->add_data(
							array(
								'location' => $context_data['location'],
								'key'      => $field_key,
							),
							$code
						);
					}
					$errors->merge_from( $valid_check );
					continue;
				}
			}

			// Validate all fields for this location (this runs custom validation callbacks).
			$valid_location_check = $this->additional_fields_controller->validate_fields_for_location( $field_values, $context_data['location'], $context_data['group'] );

			if ( is_wp_error( $valid_location_check ) && $valid_location_check->has_errors() ) {
				foreach ( $valid_location_check->get_error_codes() as $code ) {
					$valid_location_check->add_data(
						array(
							'location' => $context_data['location'],
						),
						$code
					);
				}
				$errors->merge_from( $valid_location_check );
			}

			if ( $errors->has_errors() ) {
				$invalid_groups[ $context_data['param'] ]  = $errors->get_error_message();
				$invalid_details[ $context_data['param'] ] = rest_convert_error_to_response( $errors )->get_data();
			}
		}

		if ( $invalid_groups ) {
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: List of invalid parameters. */
				esc_html( sprintf( __( 'Invalid parameter(s): %s', 'woocommerce' ), implode( ', ', array_keys( $invalid_groups ) ) ) ),
				array(
					'status'  => 400,
					'params'  => $invalid_groups,
					'details' => $invalid_details,
				)
			);
		}

		return true;
	}

	/**
	 * Get route response for PUT requests.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @throws RouteException On error.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_update_response( \WP_REST_Request $request ) {
		$validation_callback = $this->validate_callback( $request );

		if ( is_wp_error( $validation_callback ) ) {
			return $validation_callback;
		}

		/**
		 * Create (or update) Draft Order and process request data.
		 */
		$this->create_or_update_draft_order( $request );

		/**
		 * Persist additional fields, order notes and payment method for order.
		 */
		$this->update_order_from_request( $request );

		if ( $request->get_param( '__experimental_calc_totals' ) ) {
			/**
			 * Before triggering validation, ensure totals are current and in turn, things such as shipping costs are present.
			 * This is so plugins that validate other cart data (e.g. conditional shipping and payments) can access this data.
			 */
			$this->cart_controller->calculate_totals();
			/**
			 * Validate that the cart is not empty.
			 */
			$this->cart_controller->validate_cart_not_empty();

			/**
			 * Validate items and fix violations before the order is processed.
			 */
			$this->cart_controller->validate_cart();
		}

		$this->order->save();

		return $this->prepare_item_for_response(
			(object) [
				'order' => wc_get_order( $this->order ),
				'cart'  => $this->cart_controller->get_cart_instance(),
			],
			$request
		);
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
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$validation_callback = $this->validate_callback( $request );

		if ( is_wp_error( $validation_callback ) ) {
			return $validation_callback;
		}

		/**
		 * Ensure required permissions based on store settings are valid to place the order.
		 */
		$this->validate_user_can_place_order();

		/**
		 * Before triggering validation, ensure totals are current and in turn, things such as shipping costs are present.
		 * This is so plugins that validate other cart data (e.g. conditional shipping and payments) can access this data.
		 */
		$this->cart_controller->calculate_totals();

		/**
		 * Validate that the cart is not empty.
		 */
		$this->cart_controller->validate_cart_not_empty();

		/**
		 * Validate items and fix violations before the order is processed.
		 */
		$this->cart_controller->validate_cart();

		/**
		 * Persist customer session data from the request first so that OrderController::update_addresses_from_cart
		 * uses the up-to-date customer address.
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
		 * Hold coupons for the order as soon as the draft order is created.
		 */
		try {
			// $this->order->get_billing_email() is already validated by validate_order_before_payment()
			$this->order->hold_applied_coupons( $this->order->get_billing_email() );
		} catch ( \Exception $e ) {
			// Turn the Exception into a RouteException for the API.
			throw new RouteException(
				'woocommerce_rest_coupon_reserve_failed',
				esc_html( $e->getMessage() ),
				400
			);
		}

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
			$data = array_merge( $data, [ 'cart' => $this->cart_schema->get_item_response( $this->cart_controller->get_cart_for_response() ) ] );
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
				esc_html__( 'Unable to create order', 'woocommerce' ),
				500
			);
		}

		// Store order ID to session.
		$this->set_draft_order_id( $this->order->get_id() );
	}

	/**
	 * Updates a customer address field.
	 *
	 * @param \WC_Customer $customer The customer to update.
	 * @param string       $key The key of the field to update.
	 * @param mixed        $value The value to update the field to.
	 * @param string       $address_type The type of address to update (billing|shipping).
	 */
	private function update_customer_address_field( $customer, $key, $value, $address_type ) {
		$callback = "set_{$address_type}_{$key}";

		if ( is_callable( [ $customer, $callback ] ) ) {
			$customer->$callback( $value );
			return;
		}

		if ( $this->additional_fields_controller->is_field( $key ) ) {
			$this->additional_fields_controller->persist_field_for_customer( $key, $value, $customer, $address_type );
		}
	}

	/**
	 * Updates the current customer session using data from the request (e.g. address data).
	 *
	 * Address session data is synced to the order itself later on by OrderController::update_order_from_cart()
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_customer_from_request( \WP_REST_Request $request ) {
		$customer                  = WC()->customer;
		$additional_field_contexts = [
			'shipping_address' => [
				'group'    => 'shipping',
				'location' => 'address',
				'param'    => 'shipping_address',
			],
			'billing_address'  => [
				'group'    => 'billing',
				'location' => 'address',
				'param'    => 'billing_address',
			],
			'contact'          => [
				'group'    => 'other',
				'location' => 'contact',
				'param'    => 'additional_fields',
			],
		];

		foreach ( $additional_field_contexts as $context => $context_data ) {
			if ( Features::is_enabled( 'experimental-blocks' ) ) {
				$document_object = $this->get_document_object_from_rest_request( $request );
				$document_object->set_context( $context );
				$additional_fields = $this->additional_fields_controller->get_contextual_fields_for_location( $context_data['location'], $document_object );
			} else {
				$additional_fields = $this->additional_fields_controller->get_fields_for_location( $context_data['location'] );
			}

			if ( 'shipping_address' === $context_data['param'] ) {
				$field_values = (array) $request['shipping_address'] ?? ( $request['billing_address'] ?? [] );

				if ( ! WC()->cart->needs_shipping() ) {
					$field_values = $request['billing_address'] ?? [];
				}
			} else {
				$field_values = (array) $request[ $context_data['param'] ] ?? [];
			}

			if ( 'address' === $context_data['location'] ) {
				$persist_keys = array_merge( $this->additional_fields_controller->get_address_fields_keys(), [ 'email' ], array_keys( $additional_fields ) );
			} else {
				$persist_keys = array_keys( $additional_fields );
			}

			foreach ( $field_values as $key => $value ) {
				if ( in_array( $key, $persist_keys, true ) ) {
					$this->update_customer_address_field( $customer, $key, $value, $context_data['group'] );
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
		$available_gateways     = WC()->payment_gateways->get_available_payment_gateways();
		$request_payment_method = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );
		// For PUT requests, the order never requires payment, only POST does.
		$requires_payment_method = $this->order->needs_payment() && 'POST' === $request->get_method();

		if ( empty( $request_payment_method ) ) {
			if ( $requires_payment_method ) {
				throw new RouteException(
					'woocommerce_rest_checkout_missing_payment_method',
					esc_html__( 'No payment method provided.', 'woocommerce' ),
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
					esc_html__( '%s is not available for this order—please choose a different payment method', 'woocommerce' ),
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
		if ( $this->should_create_customer_account( $request ) ) {
			$customer_id = wc_create_new_customer(
				$request['billing_address']['email'],
				'',
				$request['customer_password'],
				[
					'first_name' => $request['billing_address']['first_name'],
					'last_name'  => $request['billing_address']['last_name'],
					'source'     => 'store-api',
				]
			);

			if ( is_wp_error( $customer_id ) ) {
				throw new RouteException(
					esc_html( $customer_id->get_error_code() ),
					esc_html( $customer_id->get_error_message() ),
					400
				);
			}

			// Associate customer with the order.
			$this->order->set_customer_id( $customer_id );
			$this->order->save();

			// Set the customer auth cookie.
			wc_set_customer_auth_cookie( $customer_id );
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
		if ( false === filter_var( WC()->checkout()->is_registration_enabled(), FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		// Return true if the store requires an account for all purchases. Note - checkbox is not displayed to shopper in this case.
		if ( true === filter_var( WC()->checkout()->is_registration_required(), FILTER_VALIDATE_BOOLEAN ) ) {
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
	 * This validates if the order can be placed regarding settings in WooCommerce > Settings > Accounts & Privacy
	 * If registration during checkout is disabled, guest checkout is disabled and the user is not logged in, prevent checkout.
	 *
	 * @throws RouteException If user cannot place order.
	 */
	private function validate_user_can_place_order() {
		if (
			// "woocommerce_enable_signup_and_login_from_checkout" === no.
			false === filter_var( WC()->checkout()->is_registration_enabled(), FILTER_VALIDATE_BOOLEAN ) &&
			// "woocommerce_enable_guest_checkout" === no.
			true === filter_var( WC()->checkout()->is_registration_required(), FILTER_VALIDATE_BOOLEAN ) &&
			! is_user_logged_in()
		) {
			throw new RouteException(
				'woocommerce_rest_guest_checkout_disabled',
				esc_html(
					/**
					 * Filter to customize the checkout message when a user must be logged in.
					 *
					 * @since 9.4.3
					 *
					 * @param string $message Message to display when a user must be logged in to check out.
					 */
					apply_filters(
						'woocommerce_checkout_must_be_logged_in_message',
						__( 'You must be logged in to checkout.', 'woocommerce' )
					)
				),
				403
			);
		}
	}
}
