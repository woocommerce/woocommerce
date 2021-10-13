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
	private function is_feature_enabled() {
		// Checkout signup is feature gated to WooCommerce 4.7 and newer;
		// uses updated my-account/lost-password screen from 4.7+ for
		// setting initial password.
		// This service is feature gated to plugin only, to match the
		// availability of the Checkout block (feature plugin only).
		return $this->package->feature()->is_feature_plugin_build() && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '4.7', '>=' );
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
	 * Create a user account for specified request (if necessary).
	 * If a new account is created:
	 * - The user is logged in.
	 *
	 * @param \WP_REST_Request $request The current request object being handled.
	 *
	 * @throws Exception On error.
	 * @return int The new user id, or 0 if no user was created.
	 */
	public function from_order_request( \WP_REST_Request $request ) {
		if ( ! self::is_feature_enabled() || ! $this->should_create_customer_account( $request ) ) {
			return 0;
		}

		$customer_id = $this->create_customer_account(
			$request['billing_address']['email'],
			$request['billing_address']['first_name'],
			$request['billing_address']['last_name']
		);
		// Log the customer in and associate with the order.
		wc_set_customer_auth_cookie( $customer_id );

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
		// check for whether account creation is enabled at the global level.
		$checkout = WC()->checkout();
		if ( ! $checkout instanceof \WC_Checkout ) {
			// If checkout class is not available, we have major problems, don't create account.
			return false;
		}

		if ( false === filter_var( $checkout->is_registration_enabled(), FILTER_VALIDATE_BOOLEAN ) ) {
			// Registration is not enabled for the store, so return false.
			return false;
		}

		if ( true === filter_var( $checkout->is_registration_required(), FILTER_VALIDATE_BOOLEAN ) ) {
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

		/**
		 * Fires before a customer account is registered.
		 *
		 * This hook fires before customer accounts are created and passes the form data (username, email) and an array
		 * of errors.
		 *
		 * This could be used to add extra validation logic and append errors to the array.
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
		 * @param \WP_Error $errors Error object.
		 * @param string $username Customer username.
		 * @param string $user_email Customer email address.
		 * @return \WP_Error
		 */
		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $user_email );

		if ( $errors->get_error_code() ) {
			throw new \Exception( $errors->get_error_code() );
		}

		/**
		 * Filters customer data before a customer account is registered.
		 *
		 * This hook filters customer data. It allows user data to be changed, for example, username, password, email,
		 * first name, last name, and role.
		 *
		 * @param array $customer_data An array of customer (user) data.
		 * @return array
		 */
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

		/**
		 * Fires after a customer account has been registered.
		 *
		 * This hook fires after customer accounts are created and passes the customer data.
		 *
		 * @param integer $customer_id New customer (user) ID.
		 * @param array $new_customer_data Array of customer (user) data.
		 * @param string $password_generated The generated password for the account.
		 */
		do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

		return $customer_id;
	}
}
