<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use \WP_REST_Request;
use \WC_Order;
use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Email\CustomerNewAccount;

/**
 * Service class implementing new create account behaviour for order processing.
 */
class CreateAccount {
	/**
	 * Reference to the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor.
	 *
	 * @param Package $package An instance of (Woo Blocks) Package.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * Single method for feature gating logic. Used to gate all non-private methods.
	 *
	 * @return True if Checkout sign-up feature should be made available.
	 */
	private static function is_feature_enabled() {
		// This new checkout signup flow is gated to dev builds for now.
		// The main reason for this is that we are waiting on an new
		// set-password endpoint/form in WooCommerce Core.
		// When that's available we can review this and include in feature
		// plugin alongside checkout block.
		return Package::is_experimental_build();
	}

	/**
	 * Init - register handlers for WooCommerce core email hooks.
	 */
	public function init() {
		if ( ! self::is_feature_enabled() ) {
			return;
		}

		// Override core email handlers to add our new improved "new account" email.
		add_action(
			'woocommerce_email',
			function ( $wc_emails_instance ) {
				// Remove core "new account" handler; we are going to replace it.
				remove_action( 'woocommerce_created_customer_notification', array( $wc_emails_instance, 'customer_new_account' ), 10, 3 );

				// Add custom "new account" handler.
				add_action(
					'woocommerce_created_customer_notification',
					function( $customer_id, $new_customer_data = array(), $password_generated = false ) use ( $wc_emails_instance ) {
						// If this is a block-based signup, send a new email
						// with password reset link (no password in email).
						if ( isset( $new_customer_data['is_checkout_block_customer_signup'] ) ) {
							$this->customer_new_account( $customer_id, $new_customer_data );
							return;
						}

						// Otherwise, trigger the existing legacy email (with new password inline).
						$wc_emails_instance->customer_new_account( $customer_id, $new_customer_data, $password_generated );
					},
					10,
					3
				);
			}
		);
	}

	/**
	 * Trigger new account email.
	 * This is intended as a replacement to WC_Emails::customer_new_account(),
	 * with a set password link instead of emailing the new password in email
	 * content.
	 *
	 * @param int   $customer_id       The ID of the new customer account.
	 * @param array $new_customer_data Assoc array of data for the new account.
	 */
	public function customer_new_account( $customer_id = 0, array $new_customer_data = array() ) {
		if ( ! self::is_feature_enabled() ) {
			return;
		}

		if ( ! $customer_id ) {
			return;
		}

		$new_account_email = new CustomerNewAccount( $this->package );
		$new_account_email->trigger( $customer_id, $new_customer_data );
	}

	/**
	 * Create a user account for specified order and request (if necessary).
	 * If a new account is created:
	 * - The order is associated with the account.
	 * - The user is logged in.
	 *
	 * @param \WC_Order        $order   The order currently being processed.
	 * @param \WP_REST_Request $request The current request object being handled.
	 *
	 * @throws Exception On error.
	 * @return int The new user id, or 0 if no user was created.
	 */
	public function from_order_request( \WC_Order $order, \WP_REST_Request $request ) {
		if ( ! self::is_feature_enabled() || ! $this->should_create_customer_account( $request ) ) {
			return 0;
		}

		$customer_id = $this->create_customer_account(
			$order->get_billing_email(),
			$order->get_billing_first_name(),
			$order->get_billing_last_name()
		);

		// Log the customer in and associate with the order.
		wc_set_customer_auth_cookie( $customer_id );
		$order->set_customer_id( get_current_user_id() );

		return $customer_id;
	}

	/**
	 * Check request options and store (shop) config to determine if a user account
	 * should be created as part of order processing.
	 *
	 * @param \WP_REST_Request $request The current request object being handled.
	 *
	 * @return boolean True if a new user account should be created.
	 */
	protected function should_create_customer_account( \WP_REST_Request $request ) {
		if ( is_user_logged_in() ) {
			// User is already logged in - no need to create an account.
			return false;
		}

		// From here we know that the shopper is not logged in.

		if ( false === filter_var( get_option( 'woocommerce_enable_guest_checkout' ), FILTER_VALIDATE_BOOLEAN ) ) {
			// Store requires an account for all checkouts (purchases).
			// Create an account independent of shopper option in $request.
			// Note - checkbox is not displayed to shopper in this case.
			return true;
		}

		// From here we know that the store allows guest checkout;
		// shopper can choose whether they sign up (`should_create_account`).

		if ( true === filter_var( $request['should_create_account'], FILTER_VALIDATE_BOOLEAN ) ) {
			// User has requested an account as part of checkout processing.
			return true;
		}

		return false;
	}

	/**
	 * Convert an account creation error to an exception.
	 *
	 * @param \WP_Error $error An error object.
	 *
	 * @return Exception.
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

	/**
	 * Create a new account for a customer (using a new blocks-specific PHP API).
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
	 *
	 * @return int User id if successful
	 */
	private function create_customer_account( $user_email, $first_name, $last_name ) {
		if ( empty( $user_email ) || ! is_email( $user_email ) ) {
			throw new \Exception( 'registration-error-invalid-email' );
		}

		if ( email_exists( $user_email ) ) {
			throw new \Exception( 'registration-error-email-exists' );
		}

		$username = wc_create_new_customer_username( $user_email );

		// Handle password creation.
		$password           = wp_generate_password();
		$password_generated = true;

		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		do_action( 'woocommerce_register_post', $username, $user_email, $errors );

		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $user_email );

		if ( $errors->get_error_code() ) {
			throw new \Exception( $errors->get_error_code() );
		}

		$new_customer_data = apply_filters(
			'woocommerce_new_customer_data',
			array(
				'is_checkout_block_customer_signup' => true,
				'user_login'                        => $username,
				'user_pass'                         => $password,
				'user_email'                        => $user_email,
				'first_name'                        => $first_name,
				'last_name'                         => $last_name,
				'role'                              => 'customer',
			)
		);

		$customer_id = wp_insert_user( $new_customer_data );

		if ( is_wp_error( $customer_id ) ) {
			throw $this->map_create_account_error( $customer_id );
		}

		// Set account flag to remind customer to update generated password.
		update_user_option( $customer_id, 'default_password_nag', true, true );

		do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

		return $customer_id;
	}
}
