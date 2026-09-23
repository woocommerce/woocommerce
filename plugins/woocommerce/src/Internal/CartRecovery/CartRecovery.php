<?php
/**
 * CartRecovery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CartRecovery;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Cache_Helper;
use WC_Cart;
use WC_Customer;
use WC_Email_Customer_Cart_Recovery;
use WC_Geolocation;
use WC_Product;
use WC_Product_Variation;
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

	private const MAX_IDLE = DAY_IN_SECONDS;

	private const DEFAULT_DAILY_LIMIT = 500;

	private const LOG_SOURCE = 'cart-recovery';

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
		add_action( self::ACTION_HOOK, array( $this, 'handle_send' ), 10, 1 );
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
	 * Run the send-time checks for a session and send, reschedule or skip.
	 *
	 * @internal
	 *
	 * @param mixed $key Session key the job was queued for.
	 */
	public function handle_send( $key ): void {
		$key   = (string) $key;
		$email = $this->get_email();
		if ( null === $email || ! $email->is_enabled() ) {
			$this->log_skip( $key, 'email disabled' );
			return;
		}

		$session = $this->load_session( $key );
		$data    = $session[ self::SESSION_KEY ] ?? null;
		if ( ! is_array( $data ) || ( $data['key'] ?? '' ) !== $key || ! is_email( $data['email'] ?? '' ) ) {
			$this->log_skip( $key, 'no recovery data' );
			return;
		}

		$cart = $session['cart'] ?? array();
		if ( ! is_array( $cart ) || empty( $cart ) ) {
			$this->log_skip( $key, 'empty cart' );
			return;
		}

		$now       = time();
		$last_seen = (int) ( $data['last_seen'] ?? 0 );
		if ( $now - $last_seen > self::MAX_IDLE ) {
			$this->log_skip( $key, 'idle too long' );
			return;
		}

		$due = $last_seen + $this->get_delay_seconds();
		if ( $due > $now ) {
			$this->reschedule( $key, $due );
			return;
		}

		$address   = (string) $data['email'];
		$limit_key = 'cart_recovery_email_' . wp_hash( strtolower( trim( $address ) ) );
		if ( WC_Rate_Limiter::retried_too_soon( $limit_key ) || $this->daily_limit_reached() ) {
			$this->log_skip( $key, 'rate limited' );
			return;
		}

		$settings = $this->get_settings();
		$total    = (float) ( $session['cart_totals']['total'] ?? 0 );
		if ( $total < (float) ( $settings['min_cart_total'] ?? 0 ) ) {
			$this->reschedule( $key, $now + $this->get_delay_seconds() );
			return;
		}

		if ( $this->has_excluded_role( $key, (array) ( $settings['excluded_roles'] ?? array() ) ) ) {
			$this->log_skip( $key, 'excluded role' );
			return;
		}

		if ( $this->has_order_since( $key, $address, (int) ( $data['captured_at'] ?? $now ) ) ) {
			$this->log_skip( $key, 'order placed' );
			return;
		}

		$this->prime_products( $cart );

		if ( $this->has_excluded_category( $cart, array_map( 'absint', (array) ( $settings['excluded_categories'] ?? array() ) ) ) ) {
			$this->log_skip( $key, 'excluded category' );
			return;
		}

		$items = $this->get_items( $cart );
		if ( empty( $items ) ) {
			$this->reschedule( $key, $now + $this->get_delay_seconds() );
			return;
		}

		$recovery = array(
			'email'        => $address,
			'items'        => $items,
			'recovery_url' => $this->get_recovery_url( $items ),
		);

		/**
		 * Filter whether to send the cart recovery email for a session.
		 *
		 * @since 11.3.0
		 *
		 * @param bool  $eligible Whether to send. Only a strict `true` sends.
		 * @param array $recovery Email address, items (product and quantity) and recovery URL.
		 */
		if ( true !== apply_filters( 'woocommerce_cart_recovery_is_eligible', true, $recovery ) ) {
			$this->log_skip( $key, 'filtered out' );
			return;
		}

		// Set before sending so two runners cannot both pass the check.
		WC_Rate_Limiter::set_rate_limit( $limit_key, DAY_IN_SECONDS );
		$this->increment_daily_count();

		if ( ! $email->trigger( $recovery ) ) {
			$this->log_skip( $key, 'send failed' );
		}
	}

	/**
	 * The recovery email from the mailer.
	 *
	 * @return WC_Email_Customer_Cart_Recovery|null
	 */
	private function get_email(): ?WC_Email_Customer_Cart_Recovery {
		$emails = WC()->mailer()->get_emails();
		$email  = $emails['WC_Email_Customer_Cart_Recovery'] ?? null;

		return $email instanceof WC_Email_Customer_Cart_Recovery ? $email : null;
	}

	/**
	 * Read a session row fresh from storage, with each value unserialized.
	 *
	 * @param string $key Session key.
	 * @return array
	 */
	private function load_session( string $key ): array {
		// The Store API cart-token handler writes the table without updating this cache entry.
		wp_cache_delete( WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $key, WC_SESSION_CACHE_GROUP );

		// Capture only runs under WC_Session_Handler, so the row is in the core sessions table.
		$raw = ( new WC_Session_Handler() )->get_session( $key, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		// WC_Session::set() stores each value already serialized.
		return array_map( 'maybe_unserialize', $raw );
	}

	/**
	 * Queue the job again. `unique` must be false: the running action still counts as a duplicate.
	 *
	 * @param string $key  Session key.
	 * @param int    $when Unix time to run.
	 */
	private function reschedule( string $key, int $when ): void {
		if ( 0 === as_schedule_single_action( $when, self::ACTION_HOOK, array( $key ), self::ACTION_GROUP, false, 20 ) ) {
			$this->log_skip( $key, 'reschedule failed' );
		}
	}

	/**
	 * Whether the session belongs to a user with an excluded role.
	 *
	 * @param string   $key   Session key; numeric for logged-in users.
	 * @param string[] $roles Excluded roles.
	 * @return bool
	 */
	private function has_excluded_role( string $key, array $roles ): bool {
		if ( empty( $roles ) || ! ctype_digit( $key ) ) {
			return false;
		}

		$user = get_userdata( (int) $key );
		return $user instanceof \WP_User && ! empty( array_intersect( $roles, $user->roles ) );
	}

	/**
	 * Whether a paid or on-hold order exists for this shopper since capture.
	 *
	 * @param string $key     Session key; numeric for logged-in users.
	 * @param string $address Captured email.
	 * @param int    $since   Capture time.
	 * @return bool
	 */
	private function has_order_since( string $key, string $address, int $since ): bool {
		$customer = array( $address );
		if ( ctype_digit( $key ) ) {
			$customer[] = (int) $key;
		}

		$ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'customer'     => $customer,
				'status'       => array_merge( (array) wc_get_is_paid_statuses(), array( OrderStatus::ON_HOLD ) ),
				'date_created' => '>' . $since,
				'limit'        => 1,
				'return'       => 'ids',
			)
		);

		return ! empty( $ids );
	}

	/**
	 * Load every product and parent in the cart in one query.
	 *
	 * @param array $cart Session cart.
	 */
	private function prime_products( array $cart ): void {
		$ids = array();
		foreach ( $cart as $item ) {
			$ids[] = absint( $item['product_id'] ?? 0 );
			$ids[] = absint( $item['variation_id'] ?? 0 );
		}

		_prime_post_caches( array_values( array_filter( array_unique( $ids ) ) ) );
	}

	/**
	 * Whether a cart product is in an excluded category. Parent categories cover their children.
	 *
	 * @param array $cart     Session cart.
	 * @param int[] $excluded Excluded category IDs.
	 * @return bool
	 */
	private function has_excluded_category( array $cart, array $excluded ): bool {
		if ( empty( $excluded ) ) {
			return false;
		}

		foreach ( $cart as $item ) {
			// wc_get_product_cat_ids() includes ancestor categories.
			if ( array_intersect( $excluded, wc_get_product_cat_ids( absint( $item['product_id'] ?? 0 ) ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cart items that can be bought again through a checkout link.
	 *
	 * @param array $cart Session cart.
	 * @return array<int, array{product: WC_Product, quantity: int}>
	 */
	private function get_items( array $cart ): array {
		$items = array();
		foreach ( $cart as $item ) {
			$product_id = absint( $item['variation_id'] ?? 0 ) ? absint( $item['variation_id'] ) : absint( $item['product_id'] ?? 0 );
			$product    = wc_get_product( $product_id );

			if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			// Checkout links cannot carry an "any" attribute choice (#61446).
			if ( $product instanceof WC_Product_Variation && in_array( '', $product->get_variation_attributes(), true ) ) {
				continue;
			}

			$items[] = array(
				'product'  => $product,
				'quantity' => max( 1, absint( $item['quantity'] ?? 1 ) ),
			);
		}

		return $items;
	}

	/**
	 * Checkout link that rebuilds the cart, tagged for order attribution.
	 *
	 * @param array<int, array{product: WC_Product, quantity: int}> $items Items.
	 * @return string
	 */
	private function get_recovery_url( array $items ): string {
		$products = array();
		foreach ( $items as $item ) {
			$products[] = $item['product']->get_id() . ':' . $item['quantity'];
		}

		return add_query_arg(
			array(
				'checkout-link' => 'true',
				'products'      => implode( ',', $products ),
				'utm_source'    => 'woocommerce',
				'utm_medium'    => 'email',
				'utm_campaign'  => 'cart_recovery',
			),
			home_url( '/' )
		);
	}

	/**
	 * Transient holding today's send count.
	 *
	 * @return string
	 */
	private function get_daily_count_key(): string {
		return 'wc_cart_recovery_sent_' . gmdate( 'Ymd' );
	}

	/**
	 * Whether today's site-wide send limit is reached.
	 *
	 * @return bool
	 */
	private function daily_limit_reached(): bool {
		/**
		 * Filter the maximum number of cart recovery emails the site sends per day.
		 *
		 * @since 11.3.0
		 *
		 * @param int $limit Daily limit. Default 500.
		 */
		$limit = absint( apply_filters( 'woocommerce_cart_recovery_daily_limit', self::DEFAULT_DAILY_LIMIT ) );

		return (int) get_transient( $this->get_daily_count_key() ) >= $limit;
	}

	/**
	 * Count one send against today's limit.
	 */
	private function increment_daily_count(): void {
		$key = $this->get_daily_count_key();
		set_transient( $key, (int) get_transient( $key ) + 1, DAY_IN_SECONDS );
	}

	/**
	 * Log why a job did not send. No email addresses or raw user IDs.
	 *
	 * @param string $key    Session key.
	 * @param string $reason Reason.
	 */
	private function log_skip( string $key, string $reason ): void {
		wc_get_logger()->debug(
			sprintf( 'Cart recovery skipped for session %s: %s.', substr( wp_hash( $key ), 0, 12 ), $reason ),
			array( 'source' => self::LOG_SOURCE )
		);
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
