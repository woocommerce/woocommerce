<?php
/**
 * CartRecovery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CartRecovery;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Cart;
use WC_Customer;
use WC_Geolocation;
use WC_Rate_Limiter;
use WC_Session;
use WC_Session_Handler;
use WP_Error;

/**
 * Captures the checkout email into the customer session and sends one cart recovery email per session.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
class CartRecovery {

	public const FEATURE_NAME = 'cart_recovery';

	public const ACTION_HOOK = 'woocommerce_send_cart_recovery';

	public const ACTION_GROUP = 'woocommerce-cart-recovery';

	public const SESSION_KEY = 'cart_recovery';

	private const SETTINGS_OPTION = 'woocommerce_customer_cart_recovery_settings';

	// Copies of WC_Email_Customer_Cart_Recovery constants; that class is not loaded on the request path.
	private const MIN_DELAY_MINUTES     = 15;
	private const MAX_DELAY_MINUTES     = 1380;
	private const DEFAULT_DELAY_MINUTES = 60;

	private const ACTIVITY_INTERVAL = 5 * MINUTE_IN_SECONDS;

	// ponytail: one new scheduled session per IP per minute; shared IPs (offices, carrier NAT) can lose captures. Switch to a counter if that shows up.
	private const IP_WINDOW = MINUTE_IN_SECONDS;

	/**
	 * Register the hooks that must exist whether or not the feature is on.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'handle_feature_enabled_changed' ), 10, 2 );
		add_action( 'init', array( $this, 'maybe_register_hooks' ), 1 );
	}

	/**
	 * Register the feature hooks when the flag is on.
	 *
	 * @internal
	 */
	public function maybe_register_hooks(): void {
		if ( ! FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) ) {
			return;
		}

		add_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this, 'handle_woocommerce_store_api_cart_update_customer_from_request' ), 10, 1 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_woocommerce_checkout_update_order_review' ), 10, 1 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'handle_woocommerce_after_checkout_validation' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'handle_woocommerce_cart_loaded_from_session' ), 10, 1 );
		add_action( 'woocommerce_guest_session_to_user_id', array( $this, 'handle_woocommerce_guest_session_to_user_id' ), 10, 2 );
		add_action( 'woocommerce_cart_emptied', array( $this, 'clear' ), 10, 0 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'clear' ), 10, 0 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'clear' ), 10, 0 );
	}

	/**
	 * Unschedule every pending job when the feature is turned off.
	 *
	 * @internal
	 *
	 * @param mixed $feature_id Feature ID.
	 * @param mixed $enabled    Whether the feature is now enabled.
	 */
	public function handle_feature_enabled_changed( $feature_id, $enabled = false ): void {
		if ( self::FEATURE_NAME !== $feature_id || filter_var( $enabled, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		as_unschedule_all_actions( self::ACTION_HOOK );
	}

	/**
	 * Capture the email the Blocks checkout sent.
	 *
	 * @internal
	 *
	 * @param mixed $customer Customer updated from the request.
	 */
	public function handle_woocommerce_store_api_cart_update_customer_from_request( $customer ): void {
		if ( $customer instanceof WC_Customer ) {
			$this->capture( $customer->get_billing_email() );
		}
	}

	/**
	 * Capture the email from a classic checkout order review update.
	 *
	 * @internal
	 *
	 * @param mixed $post_data Serialized checkout form.
	 */
	public function handle_woocommerce_checkout_update_order_review( $post_data ): void {
		if ( ! is_string( $post_data ) ) {
			return;
		}

		parse_str( $post_data, $fields );
		$this->capture( $fields['billing_email'] ?? '' );
	}

	/**
	 * Capture the email from a classic checkout submit that failed validation.
	 *
	 * @internal
	 *
	 * @param mixed $data   Posted checkout data.
	 * @param mixed $errors Validation errors.
	 */
	public function handle_woocommerce_after_checkout_validation( $data, $errors ): void {
		if ( is_array( $data ) && $errors instanceof WP_Error && $errors->has_errors() ) {
			$this->capture( $data['billing_email'] ?? '' );
		}
	}

	/**
	 * Capture logged-in customers who reach the checkout page.
	 *
	 * @internal
	 */
	public function handle_template_redirect(): void {
		if ( is_user_logged_in() && is_checkout() && WC()->customer instanceof WC_Customer ) {
			$this->capture( WC()->customer->get_billing_email() );
		}
	}

	/**
	 * Track activity and schedule a job when the session key changed.
	 *
	 * @internal
	 *
	 * @param mixed $cart Cart loaded from the session.
	 */
	public function handle_woocommerce_cart_loaded_from_session( $cart ): void {
		$data = $this->get_data();
		if ( null === $data || ! $cart instanceof WC_Cart || $cart->is_empty() ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, $this->touch( $data ) );
	}

	/**
	 * Move recovery data to the user key when a guest logs in.
	 *
	 * @internal
	 *
	 * @param mixed $guest_key Former guest session key.
	 * @param mixed $user_key  New user session key.
	 */
	public function handle_woocommerce_guest_session_to_user_id( $guest_key, $user_key ): void {
		as_unschedule_action( self::ACTION_HOOK, array( (string) $guest_key ), self::ACTION_GROUP );

		if ( ! WC()->session instanceof WC_Session ) {
			return;
		}

		$data = WC()->session->get( self::SESSION_KEY );
		if ( is_array( $data ) ) {
			$data['key']       = (string) $user_key;
			$data['scheduled'] = false;
			WC()->session->set( self::SESSION_KEY, $data );
		}
	}

	/**
	 * Remove recovery data and its job, after an order or when the cart empties.
	 *
	 * @internal
	 */
	public function clear(): void {
		if ( ! WC()->session instanceof WC_Session ) {
			return;
		}

		$data = WC()->session->get( self::SESSION_KEY );
		if ( is_array( $data ) && ! empty( $data['key'] ) ) {
			as_unschedule_action( self::ACTION_HOOK, array( (string) $data['key'] ), self::ACTION_GROUP );
		}

		WC()->session->set( self::SESSION_KEY, null );
	}

	/**
	 * Send delay in seconds, from the email setting and the delay filter.
	 *
	 * @since 11.3.0
	 *
	 * @return int
	 */
	public function get_delay_seconds(): int {
		$minutes = (int) ( $this->get_settings()['delay_minutes'] ?? self::DEFAULT_DELAY_MINUTES );
		$minutes = max( self::MIN_DELAY_MINUTES, min( self::MAX_DELAY_MINUTES, $minutes ) );

		/**
		 * Filter the cart recovery email delay, in seconds after the shopper's last activity.
		 *
		 * @since 11.3.0
		 *
		 * @param int $delay_seconds Delay in seconds. Defaults to the `Send after (minutes)` setting.
		 */
		$seconds = absint( apply_filters( 'woocommerce_cart_recovery_delay_seconds', $minutes * MINUTE_IN_SECONDS ) );

		return max( MINUTE_IN_SECONDS, $seconds );
	}

	/**
	 * Store the email and schedule the job when capture is allowed.
	 *
	 * @param mixed $email Email to capture.
	 */
	private function capture( $email ): void {
		if ( ! is_string( $email ) || ! is_email( $email ) || ! $this->can_capture() ) {
			return;
		}

		$now  = time();
		$data = $this->get_data() ?? array(
			'key'         => (string) WC()->session->get_customer_id(),
			'captured_at' => $now,
			'last_seen'   => 0,
			'scheduled'   => false,
		);

		$data['email'] = sanitize_email( $email );

		WC()->session->set( self::SESSION_KEY, $this->touch( $data ) );
	}

	/**
	 * Refresh last_seen through the throttle and schedule the job if it is not queued yet.
	 *
	 * @param array $data Recovery data.
	 * @return array
	 */
	private function touch( array $data ): array {
		$now = time();
		if ( $now - (int) $data['last_seen'] >= self::ACTIVITY_INTERVAL ) {
			$data['last_seen'] = $now;
		}

		if ( ! $data['scheduled'] && $this->is_email_enabled() && $this->ip_allows_schedule() ) {
			as_schedule_single_action( (int) $data['last_seen'] + $this->get_delay_seconds(), self::ACTION_HOOK, array( (string) $data['key'] ), self::ACTION_GROUP, true, 20 );
			$data['scheduled'] = true;
		}

		return $data;
	}

	/**
	 * Recovery data for the current session, or null. Drops data captured under another key.
	 *
	 * @return array|null
	 */
	private function get_data(): ?array {
		if ( ! WC()->session instanceof WC_Session ) {
			return null;
		}

		$data = WC()->session->get( self::SESSION_KEY );
		if ( ! is_array( $data ) ) {
			return null;
		}

		// A `?session=` clone copies this key into another visitor's session; the key stops matching.
		if ( ( $data['key'] ?? '' ) !== (string) WC()->session->get_customer_id() ) {
			WC()->session->set( self::SESSION_KEY, null );
			return null;
		}

		return $data;
	}

	/**
	 * Whether this request can capture: supported session storage, a non-empty cart and the email enabled.
	 *
	 * @return bool
	 */
	private function can_capture(): bool {
		// The send job reads the core sessions table, so other session handlers are not supported.
		return WC()->session instanceof WC_Session_Handler
			&& WC()->cart instanceof WC_Cart
			&& ! WC()->cart->is_empty()
			&& $this->is_email_enabled();
	}

	/**
	 * Whether the email is enabled, read from the autoloaded settings option.
	 *
	 * @return bool
	 */
	private function is_email_enabled(): bool {
		return 'yes' === ( $this->get_settings()['enabled'] ?? 'no' );
	}

	/**
	 * Email settings saved by WC_Email_Customer_Cart_Recovery.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$settings = get_option( self::SETTINGS_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Allow one newly scheduled session per IP per window.
	 *
	 * @return bool
	 */
	private function ip_allows_schedule(): bool {
		$ip = WC_Geolocation::get_ip_address();
		if ( '' === $ip ) {
			return true;
		}

		$limit_key = 'cart_recovery_ip_' . wp_hash( $ip );
		if ( WC_Rate_Limiter::retried_too_soon( $limit_key ) ) {
			return false;
		}

		WC_Rate_Limiter::set_rate_limit( $limit_key, self::IP_WINDOW );
		return true;
	}
}
