<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Exception;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Utilities\DiscountsUtil;
use Automattic\WooCommerce\Utilities\ShippingUtil;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use Automattic\WooCommerce\StoreApi\Utilities\ArrayUtils;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * OrderController class.
 * Helper class which creates and syncs orders with the cart.
 */
class OrderController {

	/**
	 * Checkout fields controller.
	 *
	 * @var CheckoutFields
	 */
	private CheckoutFields $additional_fields_controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->additional_fields_controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Create order and set props based on global settings.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @return \WC_Order A new order object.
	 */
	public function create_order_from_cart() {
		if ( wc()->cart->is_empty() ) {
			throw new RouteException(
				'woocommerce_rest_cart_empty',
				__( 'Cannot create order from empty cart.', 'woocommerce' ),
				400
			);
		}

		add_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		$order = new \WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->set_created_via( 'store-api' );
		$this->update_order_from_cart( $order );

		remove_filter( 'woocommerce_default_order_status', array( $this, 'default_order_status' ) );

		return $order;
	}

	/**
	 * Update an order using data from the current cart.
	 *
	 * @param \WC_Order $order The order object to update.
	 * @param boolean   $update_totals Whether to update totals or not.
	 */
	public function update_order_from_cart( \WC_Order $order, $update_totals = true ) {
		/**
		 * This filter ensures that local pickup locations are still used for order taxes by forcing the address used to
		 * calculate tax for an order to match the current address of the customer.
		 *
		 * -    The method `$customer->get_taxable_address()` runs the filter `woocommerce_customer_taxable_address`.
		 * -    While we have a session, our `ShippingController::filter_taxable_address` function uses this hook to set
		 *      the customer address to the pickup location address if local pickup is the chosen method.
		 *
		 * Without this code in place, `$customer->get_taxable_address()` is not used when order taxes are calculated,
		 * resulting in the wrong taxes being applied with local pickup.
		 *
		 * The alternative would be to instead use `woocommerce_order_get_tax_location` to return the pickup location
		 * address directly, however since we have the customer filter in place we don't need to duplicate effort.
		 *
		 * @see \WC_Abstract_Order::get_tax_location()
		 */
		add_filter(
			'woocommerce_order_get_tax_location',
			function ( $location ) {

				if ( ! is_null( wc()->customer ) ) {

					$taxable_address = wc()->customer->get_taxable_address();

					$location = array(
						'country'  => $taxable_address[0],
						'state'    => $taxable_address[1],
						'postcode' => $taxable_address[2],
						'city'     => $taxable_address[3],
					);
				}

				return $location;
			}
		);

		// Ensure cart is current.
		if ( $update_totals ) {
			wc()->cart->calculate_totals();
		}

		// Update the current order to match the current cart.
		$this->update_line_items_from_cart( $order );
		$this->update_addresses_from_cart( $order );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
		$order->set_customer_id( get_current_user_id() );
		$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
		$order->set_payment_method( PaymentUtils::get_default_payment_method() );
		$order->update_meta_data( 'is_vat_exempt', wc_bool_to_string( wc()->cart->get_customer()->get_is_vat_exempt() ) );
		$order->calculate_totals();
	}

	/**
	 * Copies order data to customer object (not the session), so values persist for future checkouts.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function sync_customer_data_with_order( \WC_Order $order ) {
		if ( $order->get_customer_id() ) {
			$customer = new \WC_Customer( $order->get_customer_id() );
			$customer->set_props(
				array(
					'billing_first_name'  => $order->get_billing_first_name(),
					'billing_last_name'   => $order->get_billing_last_name(),
					'billing_company'     => $order->get_billing_company(),
					'billing_address_1'   => $order->get_billing_address_1(),
					'billing_address_2'   => $order->get_billing_address_2(),
					'billing_city'        => $order->get_billing_city(),
					'billing_state'       => $order->get_billing_state(),
					'billing_postcode'    => $order->get_billing_postcode(),
					'billing_country'     => $order->get_billing_country(),
					'billing_email'       => $order->get_billing_email(),
					'billing_phone'       => $order->get_billing_phone(),
					'shipping_first_name' => $order->get_shipping_first_name(),
					'shipping_last_name'  => $order->get_shipping_last_name(),
					'shipping_company'    => $order->get_shipping_company(),
					'shipping_address_1'  => $order->get_shipping_address_1(),
					'shipping_address_2'  => $order->get_shipping_address_2(),
					'shipping_city'       => $order->get_shipping_city(),
					'shipping_state'      => $order->get_shipping_state(),
					'shipping_postcode'   => $order->get_shipping_postcode(),
					'shipping_country'    => $order->get_shipping_country(),
					'shipping_phone'      => $order->get_shipping_phone(),
				)
			);

			$this->additional_fields_controller->sync_customer_additional_fields_with_order( $order, $customer );

			$customer->save();
		}
	}

	/**
	 * Final validation ran before payment is taken.
	 *
	 * By this point we have an order populated with customer data and items.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param \WC_Order $order Order object.
	 */
	public function validate_order_before_payment( \WC_Order $order ) {
		$needs_shipping          = wc()->cart->needs_shipping();
		$chosen_shipping_methods = wc()->session->get( 'chosen_shipping_methods', [] );

		$this->validate_coupons( $order );
		$this->validate_email( $order );
		$this->validate_selected_shipping_methods( $needs_shipping, $chosen_shipping_methods );
		$this->validate_addresses( $order, $needs_shipping );

		// Perform custom validations.
		$this->perform_custom_order_validation( $order );
	}

	/**
	 * Final validation for existing orders, ran before payment is taken.
	 *
	 * By this point we have an order populated with customer data and items.
	 *
	 * Since the cart is not involved, we don't validate shipping methods and assume the order already
	 * contains the correct shipping items.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param \WC_Order $order Order object.
	 */
	public function validate_existing_order_before_payment( \WC_Order $order ) {
		$needs_shipping = $order->needs_shipping();

		$this->validate_coupons( $order, true );
		$this->validate_email( $order );
		$this->validate_addresses( $order, $needs_shipping );

		// Perform custom validations.
		$this->perform_custom_order_validation( $order );
	}

	/**
	 * Perform custom order validation via WooCommerce hooks.
	 *
	 * Allows plugins to perform custom validation before payment.
	 *
	 * @param \WC_Order $order Order object.
	 * @throws RouteException Exception if validation fails.
	 */
	protected function perform_custom_order_validation( \WC_Order $order ) {
		$validation_errors = new \WP_Error();

		/**
		 * Allow plugins to perform custom validation before payment.
		 *
		 * Plugins can add errors to the $validation_errors object.
		 *
		 * @param \WC_Order $order             The order object.
		 * @param \WP_Error $validation_errors WP_Error object to add custom errors to.
		 * @since 9.9.0
		 */
		do_action( 'woocommerce_checkout_validate_order_before_payment', $order, $validation_errors );

		// Check if there are any errors after custom validation.
		if ( $validation_errors->has_errors() ) {
			throw new RouteException(
				'woocommerce_rest_checkout_custom_validation_error',
				esc_html( implode( ' ', $validation_errors->get_error_messages() ) ),
				400
			);
		}
	}

	/**
	 * Convert a coupon code to a coupon object.
	 *
	 * @param string $coupon_code Coupon code.
	 * @return \WC_Coupon Coupon object.
	 */
	protected function get_coupon( $coupon_code ) {
		return new \WC_Coupon( $coupon_code );
	}

	/**
	 * Validate coupons applied to the order and remove those that are not valid.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param \WC_Order $order Order object.
	 * @param bool      $use_order_data Whether to use order data or cart data.
	 */
	protected function validate_coupons( \WC_Order $order, bool $use_order_data = false ) {
		$coupon_codes  = $order->get_coupon_codes();
		$coupons       = array_filter( array_map( array( $this, 'get_coupon' ), $coupon_codes ) );
		$validators    = array( 'validate_coupon_email_restriction', 'validate_coupon_usage_limit' );
		$coupon_errors = array();

		foreach ( $coupons as $coupon ) {
			try {
				array_walk(
					$validators,
					function ( $validator, $index, $params ) {
						call_user_func_array( array( $this, $validator ), $params );
					},
					array( $coupon, $order )
				);
			} catch ( Exception $error ) {
				$coupon_errors[ $coupon->get_code() ] = $error->getMessage();
			}
		}

		if ( $coupon_errors ) {
			// Remove all coupons that were not valid.
			if ( $use_order_data ) {
				$error_code = 'woocommerce_rest_order_coupon_errors';

				foreach ( $coupon_errors as $coupon_code => $message ) {
					$order->remove_coupon( $coupon_code );
				}

				// Recalculate totals.
				$order->calculate_totals();
			} else {
				$error_code = 'woocommerce_rest_cart_coupon_errors';

				foreach ( $coupon_errors as $coupon_code => $message ) {
					wc()->cart->remove_coupon( $coupon_code );
				}

				// Recalculate totals.
				wc()->cart->calculate_totals();

				// Re-sync order with cart.
				$this->update_order_from_cart( $order );
			}

			// Return exception so customer can review before payment.
			if ( 1 === count( $coupon_errors ) && $use_order_data ) {
				$error_message = sprintf(
					/* translators: %1$s Coupon codes, %2$s Reason */
					__( '"%1$s" was removed from the order. %2$s', 'woocommerce' ),
					array_keys( $coupon_errors )[0],
					array_values( $coupon_errors )[0],
				);
			} elseif ( 1 === count( $coupon_errors ) ) {
				$error_message = sprintf(
					/* translators: %1$s Coupon codes, %2$s Reason */
					__( '"%1$s" was removed from the cart. %2$s', 'woocommerce' ),
					array_keys( $coupon_errors )[0],
					array_values( $coupon_errors )[0],
				);
			} elseif ( $use_order_data ) {
				$error_message = sprintf(
					/* translators: %s Coupon codes. */
					__( 'Invalid coupons were removed from the order: "%s"', 'woocommerce' ),
					implode( '", "', array_keys( $coupon_errors ) )
				);
			} else {
				$error_message = sprintf(
					/* translators: %s Coupon codes. */
					__( 'Invalid coupons were removed from the cart: "%s"', 'woocommerce' ),
					implode( '", "', array_keys( $coupon_errors ) )
				);
			}

			throw new RouteException( $error_code, $error_message, 409, array( 'removed_coupons' => $coupon_errors ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Validates the customer email. This is a required field.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param \WC_Order $order Order object.
	 */
	protected function validate_email( \WC_Order $order ) {
		$email = $order->get_billing_email();

		if ( empty( $email ) ) {
			throw new RouteException(
				'woocommerce_rest_missing_email_address',
				__( 'A valid email address is required', 'woocommerce' ),
				400
			);
		}

		if ( ! is_email( $email ) ) {
			throw new RouteException(
				'woocommerce_rest_invalid_email_address',
				sprintf(
					/* translators: %s provided email. */
					__( 'The provided email address (%s) is not valid—please provide a valid email address', 'woocommerce' ),
					esc_html( $email )
				),
				400
			);
		}
	}

	/**
	 * Validates customer address data based on the locale to ensure required fields are set.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param \WC_Order $order Order object.
	 * @param bool      $needs_shipping Whether the order needs shipping.
	 */
	protected function validate_addresses( \WC_Order $order, bool $needs_shipping ) {
		$errors           = new \WP_Error();
		$billing_country  = $order->get_billing_country();
		$shipping_country = $order->get_shipping_country();

		if ( $needs_shipping ) {
			$local_pickup_method_ids                      = LocalPickupUtils::get_local_pickup_method_ids();
			$selected_shipping_rates                      = ShippingUtil::get_selected_shipping_rates_from_packages( WC()->shipping()->get_packages() );
			$selected_shipping_rates_are_all_local_pickup = ArrayUtil::array_all(
				$selected_shipping_rates,
				function ( $rate ) use ( $local_pickup_method_ids ) {
					return in_array( $rate->get_method_id(), $local_pickup_method_ids, true );
				}
			);

			// If only local pickup is selected, we don't need to validate the shipping country.
			if ( ! $selected_shipping_rates_are_all_local_pickup && ! $this->validate_allowed_country( $shipping_country, (array) wc()->countries->get_shipping_countries() ) ) {
				throw new RouteException(
					'woocommerce_rest_invalid_address_country',
					sprintf(
						/* translators: %s country code. */
						esc_html__( 'Sorry, we do not ship orders to the provided country (%s)', 'woocommerce' ),
						esc_html( $shipping_country )
					),
					400,
					array(
						'allowed_countries' => array_map( 'esc_html', array_keys( wc()->countries->get_shipping_countries() ) ),
					)
				);
			}
		}

		if ( ! $this->validate_allowed_country( $billing_country, (array) wc()->countries->get_allowed_countries() ) ) {
			throw new RouteException(
				'woocommerce_rest_invalid_address_country',
				sprintf(
					/* translators: %s country code. */
					esc_html__( 'Sorry, we do not allow orders from the provided country (%s)', 'woocommerce' ),
					esc_html( $billing_country )
				),
				400,
				array(
					'allowed_countries' => array_map( 'esc_html', array_keys( wc()->countries->get_allowed_countries() ) ),
				)
			);
		}

		if ( $needs_shipping ) {
			$this->validate_address_fields( $order, 'shipping', $errors );
		}
		$this->validate_address_fields( $order, 'billing', $errors );

		if ( ! $errors->has_errors() ) {
			return;
		}

		$errors_by_code = array();
		$error_codes    = $errors->get_error_codes();

		foreach ( $error_codes as $code ) {
			$errors_by_code[ $code ] = $errors->get_error_messages( $code );
		}

		// Surface errors from first code.
		foreach ( $errors_by_code as $code => $error_messages ) {
			throw new RouteException(
				'woocommerce_rest_invalid_address',
				sprintf(
					/* translators: %s Address type. */
					esc_html__( 'There was a problem with the provided %s:', 'woocommerce' ) . ' ' . esc_html( implode( ', ', $error_messages ) ),
					'shipping' === $code ? esc_html__( 'shipping address', 'woocommerce' ) : esc_html__( 'billing address', 'woocommerce' )
				),
				400,
				array(
					'errors' => $errors_by_code, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				)
			);
		}
	}

	/**
	 * Check all required address fields are set and return errors if not.
	 *
	 * @param string $country Country code.
	 * @param array  $allowed_countries List of valid country codes.
	 * @return boolean True if valid.
	 */
	protected function validate_allowed_country( $country, array $allowed_countries ) {
		return array_key_exists( $country, $allowed_countries );
	}

	/**
	 * Check all required address fields are set and return errors if not.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $address_type billing or shipping address, used in error messages.
	 * @param \WP_Error $errors Error object.
	 */
	protected function validate_address_fields( \WC_Order $order, $address_type, \WP_Error $errors ) {
		$all_locales    = wc()->countries->get_country_locale();
		$address        = $order->get_address( $address_type );
		$current_locale = $all_locales[ $address['country'] ] ?? [];

		foreach ( $all_locales['default'] as $key => $value ) {
			// If $current_locale[ $key ] is not empty, merge it with locale default, otherwise just use default locale.
			$current_locale[ $key ] = ! empty( $current_locale[ $key ] )
				? wp_parse_args( $current_locale[ $key ], $value )
				: $value;
		}

		$additional_fields = $this->additional_fields_controller->get_all_fields_from_object( $order, $address_type );

		$address = array_merge( $address, $additional_fields );

		foreach ( $current_locale as $address_field_key => $address_field ) {
			// Skip validation if field is not required.
			if ( true !== $address_field['required'] ) {
				continue;
			}

			// Check if field is not set, is an empty string, or is an empty array.
			$is_empty = ! isset( $address[ $address_field_key ] ) ||
				( is_string( $address[ $address_field_key ] ) && '' === trim( $address[ $address_field_key ] ) ) ||
				( is_array( $address[ $address_field_key ] ) && 0 === count( $address[ $address_field_key ] ) );

			if ( $is_empty ) {
				/* translators: %s Field label. */
				$errors->add( $address_type, sprintf( __( '%s is required', 'woocommerce' ), $address_field['label'] ), $address_field_key );
			}
		}

		// Validate additional fields.
		$result = $this->additional_fields_controller->validate_fields_for_location( $address, 'address', $address_type );

		if ( $result->has_errors() ) {
			// Add errors to main error object but ensure they maintain the billing/shipping error code.
			foreach ( $result->get_error_codes() as $code ) {
				$errors->add( $address_type, $result->get_error_message( $code ), $code );
			}
		}
	}

	/**
	 * Check email restrictions of a coupon against the order.
	 *
	 * @throws Exception Exception if invalid data is detected.
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 * @param \WC_Order  $order Order object.
	 */
	protected function validate_coupon_email_restriction( \WC_Coupon $coupon, \WC_Order $order ) {
		$restrictions = $coupon->get_email_restrictions();
		// Email is forced lowercase like in validate_coupon_allowed_emails.
		$billing_email = strtolower( $order->get_billing_email() );

		if ( ! empty( $restrictions ) && $billing_email && ! DiscountsUtil::is_coupon_emails_allowed( array( $billing_email ), $restrictions ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( $coupon->get_coupon_error( \WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED ) );
		}
	}

	/**
	 * Check usage restrictions of a coupon against the order.
	 *
	 * @throws Exception Exception if invalid data is detected.
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 * @param \WC_Order  $order Order object.
	 */
	protected function validate_coupon_usage_limit( \WC_Coupon $coupon, \WC_Order $order ) {
		$coupon_usage_limit = $coupon->get_usage_limit_per_user();

		if ( 0 === $coupon_usage_limit ) {
			return;
		}

		// First, we check a logged in customer usage count, which happens against their user id, billing email, and account email.
		if ( $order->get_customer_id() ) {
			// We get usage per user id and associated emails.
			$usage_count = $this->get_usage_per_aliases(
				$coupon,
				array(
					$order->get_billing_email(),
					$order->get_customer_id(),
					$this->get_email_from_user_id( $order->get_customer_id() ),
				)
			);
		} else {
			// Otherwise we check if the email doesn't belong to an existing user.
			$customer_data_store = \WC_Data_Store::load( 'customer' );

			// This will get us any user ids for the given billing email.
			$user_ids = $customer_data_store->get_user_ids_for_billing_email( array( $order->get_billing_email() ) );

			// Convert all found user ids to a list of email addresses.
			$user_emails = array_map( array( $this, 'get_email_from_user_id' ), $user_ids );

			// This matches a user against the given billing email and gets their ID/email/billing email.
			$found_user = get_user_by( 'email', $order->get_billing_email() );
			if ( $found_user ) {
				$user_ids[]    = $found_user->ID;
				$user_emails[] = $found_user->user_email;
				$user_emails[] = get_user_meta( $found_user->ID, 'billing_email', true );
			}

			// Finally, grab usage count for all found IDs and emails.
			$usage_count = $this->get_usage_per_aliases(
				$coupon,
				array_merge(
					$user_emails,
					$user_ids,
					array( $order->get_billing_email() )
				)
			);
		}

		if ( $usage_count >= $coupon_usage_limit ) {
			throw new Exception( $coupon->get_coupon_error( \WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED ) );
		}
	}

	/**
	 * Get user email from user id.
	 *
	 * @param integer $user_id User ID.
	 * @return string Email or empty string.
	 */
	private function get_email_from_user_id( $user_id ) {
		$user_data = get_userdata( $user_id );
		return $user_data ? $user_data->user_email : '';
	}

	/**
	 * Get the usage count for a coupon based on a list of aliases (ids, emails).
	 *
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 * @param array      $aliases List of aliases to check.
	 *
	 * @return integer
	 */
	private function get_usage_per_aliases( $coupon, $aliases ) {
		global $wpdb;
		$aliases        = array_unique( array_filter( $aliases ) );
		$aliases_string = "('" . implode( "','", array_map( 'esc_sql', $aliases ) ) . "')";
		$usage_count    = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value IN {$aliases_string};",
				$coupon->get_id(),
			)
		);

		$data_store = $coupon->get_data_store();
		// Coupons can be held for an x amount of time before being applied to an order, so we need to check if it's already being held in (maybe via another flow).
		$tentative_usage_count = $data_store->get_tentative_usages_for_user( $coupon->get_id(), $aliases );
		return $tentative_usage_count + $usage_count;
	}

	/**
	 * Check there is a shipping method if it requires shipping.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param boolean $needs_shipping Current order needs shipping.
	 * @param array   $chosen_shipping_methods Array of shipping methods.
	 */
	public function validate_selected_shipping_methods( $needs_shipping, $chosen_shipping_methods = array() ) {
		if ( ! $needs_shipping ) {
			return;
		}

		$exception = new RouteException(
			'woocommerce_rest_invalid_shipping_option',
			__( 'Sorry, this order requires a shipping option.', 'woocommerce' ),
			400,
			array()
		);

		if ( ! is_array( $chosen_shipping_methods ) || empty( $chosen_shipping_methods ) ) {
			throw $exception;
		}

		// Validate that the chosen shipping methods are valid according to the returned package rates.
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $package_id => $package ) {
			$chosen_rate_for_package    = $chosen_shipping_methods[ $package_id ];
			$valid_rate_ids_for_package = wp_list_pluck( $package['rates'], 'id' );

			if ( ! is_string( $chosen_rate_for_package ) || ! ArrayUtils::string_contains_array( $chosen_rate_for_package, $valid_rate_ids_for_package ) ) {
				throw $exception;
			}
		}
	}

	/**
	 * Validate a given order key against an existing order.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param integer $order_id Order ID.
	 * @param string  $order_key Order key.
	 */
	public function validate_order_key( $order_id, $order_key ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order_key || $order->get_id() !== $order_id || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			throw new RouteException( 'woocommerce_rest_invalid_order', __( 'Invalid order ID or key provided.', 'woocommerce' ), 401 );
		}
	}

	/**
	 * Get errors for order stock on failed orders.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 * @param integer $order_id Order ID.
	 */
	public function get_failed_order_stock_error( $order_id ) {
		$order = wc_get_order( $order_id );

		// Ensure order items are still stocked if paying for a failed order. Pending orders do not need this check because stock is held.
		if ( ! $order->has_status( wc_get_is_pending_statuses() ) ) {
			$quantities = array();

			foreach ( $order->get_items() as $item_key => $item ) {
				if ( $item && is_callable( array( $item, 'get_product' ) ) ) {
					$product = $item->get_product();

					if ( ! $product ) {
						continue;
					}

					$quantities[ $product->get_stock_managed_by_id() ] = isset( $quantities[ $product->get_stock_managed_by_id() ] ) ? $quantities[ $product->get_stock_managed_by_id() ] + $item->get_quantity() : $item->get_quantity();
				}
			}

			// Stock levels may already have been adjusted for this order (in which case we don't need to worry about checking for low stock).
			if ( ! $order->get_data_store()->get_stock_reduced( $order->get_id() ) ) {
				foreach ( $order->get_items() as $item_key => $item ) {
					if ( $item && is_callable( array( $item, 'get_product' ) ) ) {
						$product = $item->get_product();

						if ( ! $product ) {
							continue;
						}

						/**
						 * Filters whether or not the product is in stock for this pay for order.
						 *
						 * @param boolean True if in stock.
						 * @param \WC_Product $product Product.
						 * @param \WC_Order $order Order.
						 *
						 * @since 9.8.0-dev
						 */
						if ( ! apply_filters( 'woocommerce_pay_order_product_in_stock', $product->is_in_stock(), $product, $order ) ) {
							return array(
								'code'    => 'woocommerce_rest_out_of_stock',
								/* translators: %s: product name */
								'message' => sprintf( __( 'Sorry, "%s" is no longer in stock so this order cannot be paid for. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name() ),
							);
						}

						// We only need to check products managing stock, with a limited stock qty.
						if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
							continue;
						}

						// Check stock based on all items in the cart and consider any held stock within pending orders.
						$held_stock     = wc_get_held_stock_quantity( $product, $order->get_id() );
						$required_stock = $quantities[ $product->get_stock_managed_by_id() ];

						/**
						 * Filters whether or not the product has enough stock.
						 *
						 * @param boolean True if has enough stock.
						 * @param \WC_Product $product Product.
						 * @param \WC_Order $order Order.
						 *
						 * @since 9.8.0-dev
						 */
						if ( ! apply_filters( 'woocommerce_pay_order_product_has_enough_stock', ( $product->get_stock_quantity() >= ( $held_stock + $required_stock ) ), $product, $order ) ) {
							/* translators: 1: product name 2: quantity in stock */
							return array(
								'code'    => 'woocommerce_rest_out_of_stock',
								/* translators: %s: product name */
								'message' => sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity() - $held_stock, $product ) ),
							);
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Changes default order status to draft for orders created via this API.
	 *
	 * @return string
	 */
	public function default_order_status() {
		return 'checkout-draft';
	}

	/**
	 * Create order line items.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_line_items_from_cart( \WC_Order $order ) {
		$cart_controller = new CartController();
		$cart            = $cart_controller->get_cart_instance();
		$cart_hashes     = $cart_controller->get_cart_hashes();

		if ( $order->get_cart_hash() !== $cart_hashes['line_items'] ) {
			$order->set_cart_hash( $cart_hashes['line_items'] );
			$order->remove_order_items( 'line_item' );
			wc()->checkout->create_order_line_items( $order, $cart );
		}

		if ( $order->get_meta( '_shipping_hash' ) !== $cart_hashes['shipping'] ) {
			$order->update_meta_data( '_shipping_hash', $cart_hashes['shipping'] );
			$order->remove_order_items( 'shipping' );
			wc()->checkout->create_order_shipping_lines( $order, wc()->session->get( 'chosen_shipping_methods' ), wc()->shipping()->get_packages() );
		}

		if ( $order->get_meta( '_coupons_hash' ) !== $cart_hashes['coupons'] ) {
			$order->remove_order_items( 'coupon' );
			$order->update_meta_data( '_coupons_hash', $cart_hashes['coupons'] );
			wc()->checkout->create_order_coupon_lines( $order, $cart );
		}

		if ( $order->get_meta( '_fees_hash' ) !== $cart_hashes['fees'] ) {
			$order->update_meta_data( '_fees_hash', $cart_hashes['fees'] );
			$order->remove_order_items( 'fee' );
			wc()->checkout->create_order_fee_lines( $order, $cart );
		}

		if ( $order->get_meta( '_taxes_hash' ) !== $cart_hashes['taxes'] ) {
			$order->update_meta_data( '_taxes_hash', $cart_hashes['taxes'] );
			$order->remove_order_items( 'tax' );
			wc()->checkout->create_order_tax_lines( $order, $cart );
		}
	}

	/**
	 * Update address data from cart and/or customer session data.
	 *
	 * @param \WC_Order $order The order object to update.
	 */
	protected function update_addresses_from_cart( \WC_Order $order ) {
		$order->set_props(
			array(
				'billing_first_name'  => wc()->customer->get_billing_first_name(),
				'billing_last_name'   => wc()->customer->get_billing_last_name(),
				'billing_company'     => wc()->customer->get_billing_company(),
				'billing_address_1'   => wc()->customer->get_billing_address_1(),
				'billing_address_2'   => wc()->customer->get_billing_address_2(),
				'billing_city'        => wc()->customer->get_billing_city(),
				'billing_state'       => wc()->customer->get_billing_state(),
				'billing_postcode'    => wc()->customer->get_billing_postcode(),
				'billing_country'     => wc()->customer->get_billing_country(),
				'billing_email'       => wc()->customer->get_billing_email(),
				'billing_phone'       => wc()->customer->get_billing_phone(),
				'shipping_first_name' => wc()->customer->get_shipping_first_name(),
				'shipping_last_name'  => wc()->customer->get_shipping_last_name(),
				'shipping_company'    => wc()->customer->get_shipping_company(),
				'shipping_address_1'  => wc()->customer->get_shipping_address_1(),
				'shipping_address_2'  => wc()->customer->get_shipping_address_2(),
				'shipping_city'       => wc()->customer->get_shipping_city(),
				'shipping_state'      => wc()->customer->get_shipping_state(),
				'shipping_postcode'   => wc()->customer->get_shipping_postcode(),
				'shipping_country'    => wc()->customer->get_shipping_country(),
				'shipping_phone'      => wc()->customer->get_shipping_phone(),
			)
		);
		$this->additional_fields_controller->sync_order_additional_fields_with_customer( $order, wc()->customer );
	}
}
