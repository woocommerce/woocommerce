<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use WC_Abstract_Order;

/**
 * WooPayments incentives provider class.
 *
 * @internal
 */
class WooPayments extends Incentive {
	/**
	 * The version of the logic used to determine if the store had WooPayments in use.
	 *
	 * The determined value is stored long-term (see `has_wcpay()`), and the WooPayments plugin
	 * stores its own determination under the very same option name. Persisting the version
	 * alongside the value lets us tell a value determined by the current logic from one
	 * determined by an earlier (or by the plugin's, potentially older) logic, so a stale
	 * positive gets re-determined instead of being trusted forever.
	 *
	 * Bump this whenever the determination logic gets stricter.
	 *
	 * @var int
	 */
	private const STORE_HAD_WOOPAYMENTS_LOGIC_VERSION = 2;

	/**
	 * The transient name for incentives cache.
	 *
	 * @var string
	 */
	protected string $cache_transient_name;

	/**
	 * The transient name used to store the value for if store has orders.
	 *
	 * @var string
	 */
	protected string $store_has_orders_transient_name;

	/**
	 * The option name used to store the value for if store had WooPayments in use.
	 *
	 * @var string
	 */
	protected string $store_had_woopayments_option_name;

	/**
	 * The option name used to store the logic version that determined the store had WooPayments value.
	 *
	 * @var string
	 */
	protected string $store_had_woopayments_version_option_name;

	/**
	 * The memoized incentives to avoid fetching multiple times during a request.
	 *
	 * @var array|null
	 */
	private ?array $incentives_memo = null;

	/**
	 * Constructor.
	 *
	 * @param string $suggestion_id The suggestion ID.
	 */
	public function __construct( string $suggestion_id ) {
		parent::__construct( $suggestion_id );

		$this->cache_transient_name              = self::PREFIX . $suggestion_id . '_cache';
		$this->store_has_orders_transient_name   = self::PREFIX . $suggestion_id . '_store_has_orders';
		$this->store_had_woopayments_option_name = self::PREFIX . $suggestion_id . '_store_had_woopayments';

		$this->store_had_woopayments_version_option_name = $this->store_had_woopayments_option_name . '_version';
	}

	/**
	 * Check if an incentive should be visible.
	 *
	 * @param string $id                          The incentive ID to check for visibility.
	 * @param string $country_code                The business location country code to get incentives for.
	 * @param bool   $skip_extension_active_check Whether to skip the check for the extension plugin being active.
	 *
	 * @return boolean Whether the incentive should be visible.
	 */
	public function is_visible( string $id, string $country_code, bool $skip_extension_active_check = false ): bool {
		// Always skip the extension active check since we will check below.
		if ( false === parent::is_visible( $id, $country_code, true ) ) {
			return false;
		}

		// Instead of just extension active, we check if WooPayments is active and has an account.
		if ( ! $skip_extension_active_check && $this->is_extension_active() && $this->has_wcpay_account_data() ) {
			return false;
		}

		return true;
	}

	/**
	 * Clear the incentives cache.
	 */
	public function clear_cache() {
		delete_transient( $this->cache_transient_name );
		$this->reset_memo();
	}

	/**
	 * Reset the memoized incentives.
	 *
	 * This is useful for testing purposes.
	 */
	public function reset_memo() {
		$this->incentives_memo = null;
	}

	/**
	 * Check if the extension plugin is active.
	 *
	 * @return boolean Whether the extension plugin is active.
	 */
	protected function is_extension_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Fetches and caches eligible incentives from the WooPayments API.
	 *
	 * @param string $country_code The business location country code to get incentives for.
	 *
	 * @return array List of eligible incentives.
	 */
	protected function get_incentives( string $country_code ): array {
		if ( isset( $this->incentives_memo ) ) {
			return $this->incentives_memo;
		}

		// Get the cached data.
		$cache = get_transient( $this->cache_transient_name );

		// If the cached data is not expired, and it's a WP_Error,
		// it means there was an API error previously, and we should not retry just yet.
		if ( is_wp_error( $cache ) ) {
			// Initialize the in-memory cache and return it.
			$this->incentives_memo = array();

			return $this->incentives_memo;
		}

		// Gather the store context data.
		$store_context = array(
			'country'      => $country_code,
			// Store locale, e.g. `en_US`.
			'locale'       => get_locale(),
			// WooCommerce store active for duration in seconds.
			'active_for'   => WCAdminHelper::get_wcadmin_active_for_in_seconds(),
			'has_orders'   => $this->has_orders(),
			'has_payments' => $this->has_enabled_payment_gateways(),
			'has_wcpay'    => $this->has_wcpay(),
		);

		// Fingerprint the store context through a hash of certain entries.
		$store_context_hash = $this->generate_context_hash( $store_context );

		// Use the transient cached incentive if it exists, it is not expired,
		// and the store context hasn't changed since we last requested from the WooPayments API (based on context hash).
		if ( false !== $cache
			&& ! empty( $cache['context_hash'] ) && is_string( $cache['context_hash'] )
			&& hash_equals( $store_context_hash, $cache['context_hash'] ) ) {

			// We have a store context hash, and it matches with the current context one.
			// We can use the cached incentive data.
			// Store the incentives in the in-memory cache and return them.
			$this->incentives_memo = $cache['incentives'] ?? array();

			return $this->incentives_memo;
		}

		// By this point, we have an expired transient or the store context has changed.
		// Query for incentives by calling the WooPayments API.
		$url = add_query_arg(
			$store_context,
			'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives',
		);

		$response = wp_remote_get(
			$url,
			array(
				'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
			)
		);

		// Return early if there is an error, waiting 6 hours before the next attempt.
		if ( is_wp_error( $response ) ) {
			// Store a trimmed down, lightweight error.
			$error = new \WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				wp_remote_retrieve_response_code( $response )
			);
			// Store the error in the transient so we know this is due to an API error.
			set_transient( $this->cache_transient_name, $error, HOUR_IN_SECONDS * 6 );
			// Initialize the in-memory cache and return it.
			$this->incentives_memo = array();

			return $this->incentives_memo;
		}

		$cache_for = wp_remote_retrieve_header( $response, 'cache-for' );
		// Initialize the in-memory cache.
		$this->incentives_memo = array();

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			// Decode the results, falling back to an empty array.
			$results = json_decode( wp_remote_retrieve_body( $response ), true ) ?? array();

			// Store incentives in the in-memory cache.
			$this->incentives_memo = $results;
		}

		// Skip transient cache if `cache-for` header equals zero.
		if ( '0' === $cache_for ) {
			// If we have a transient cache that is not expired, delete it so there are no leftovers.
			if ( false !== $cache ) {
				delete_transient( $this->cache_transient_name );
			}

			return $this->incentives_memo;
		}

		// Store incentive in transient cache (together with the context hash) for the given number of seconds
		// or 1 day in seconds. Also attach a timestamp to the transient data so we know when we last fetched.
		set_transient(
			$this->cache_transient_name,
			array(
				'incentives'   => $this->incentives_memo,
				'context_hash' => $store_context_hash,
				'timestamp'    => time(),
			),
			! empty( $cache_for ) ? (int) $cache_for : DAY_IN_SECONDS
		);

		return $this->incentives_memo;
	}

	/**
	 * Check if the WooPayments payment gateway is active and set up or was at some point,
	 * or there are live-mode orders processed with it, at some moment.
	 *
	 * @return boolean Whether the store has WooPayments.
	 */
	private function has_wcpay(): bool {
		// First, get the stored value, if it exists.
		// This way we avoid costly DB queries and API calls.
		// Basically, we only want to know if WooPayments was in use in the past.
		// Since the past can't be changed, neither can this value.
		$had_wcpay = get_option( $this->store_had_woopayments_option_name );
		if ( false !== $had_wcpay ) {
			$stored_value = filter_var( $had_wcpay, FILTER_VALIDATE_BOOLEAN );

			// A cached 'no' holds whatever version produced it, since each revision of this logic
			// only narrows what qualifies: a store that didn't qualify before can't start now.
			//
			// A cached 'yes' is trusted only when the current version produced it. Earlier
			// revisions counted test-mode usage - a test-drive account, a test-mode order - as
			// the real thing and froze that, so those get re-derived once. The WooPayments
			// plugin writes this same option from its own copy of this logic, which may still
			// be an older one, so this is what lets the two ship in either order instead of
			// whichever runs first winning permanently.
			if ( ! $stored_value
				|| (int) get_option( $this->store_had_woopayments_version_option_name, 0 ) >= self::STORE_HAD_WOOPAYMENTS_LOGIC_VERSION ) {

				return $stored_value;
			}
		}

		// We need to determine the value.
		// Start with the assumption that the store didn't have WooPayments in use.
		$had_wcpay = false;

		// We consider the store to have WooPayments if there is meaningful live account data
		// in the WooPayments account cache.
		// This implies that WooPayments was active at some point and that it was connected with a live account.
		if ( $this->has_wcpay_account_data() ) {
			$had_wcpay = true;
		}

		// If there is at least one live-mode order processed with WooPayments, we consider the store to have WooPayments.
		// Test-mode orders (e.g. placed while trialing WooPayments with a test-drive account) don't count
		// since they don't represent real usage of WooPayments.
		//
		// Orders carrying no order mode meta at all don't count either, since the meta is what tells the two
		// apart. That covers orders placed before WooPayments started saving it (April 2022, WooPayments 4.1.0)
		// and orders created outside the checkout flow that saves it, like WooPayments Subscriptions renewals.
		// Such a store may end up considered incentive-eligible despite having used WooPayments, which is the
		// far less harmful direction to err in than permanently withholding incentives from a store that only
		// ever tried WooPayments out.
		if ( false === $had_wcpay && ! empty(
			wc_get_orders(
				array(
					'payment_method' => 'woocommerce_payments',
					'return'         => 'ids',
					'limit'          => 1,
					'orderby'        => 'none',
					// Use the order mode meta saved by WooPayments to only count live-mode orders.
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'       => '_wcpay_mode',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value'     => 'prod',
				)
			)
		) ) {
			$had_wcpay = true;
		}

		// Store the value, and the logic version that determined it, for future use.
		update_option( $this->store_had_woopayments_option_name, $had_wcpay ? 'yes' : 'no' );
		update_option( $this->store_had_woopayments_version_option_name, self::STORE_HAD_WOOPAYMENTS_LOGIC_VERSION );

		return $had_wcpay;
	}

	/**
	 * Check if there is meaningful live account data in the WooPayments account cache.
	 *
	 * Sandbox accounts don't count: a test-drive account is a trial of WooPayments,
	 * not actual use of it. This mirrors how WooPayments itself decides whether an
	 * account belongs to a genuine live merchant (see `WC_Payments_Account::maybe_record_kyc_completion_date()`).
	 *
	 * @return boolean
	 */
	private function has_wcpay_account_data(): bool {
		$account_data = get_option( 'wcpay_account_data', array() );
		$account      = $account_data['data'] ?? array();

		if ( empty( $account['account_id'] ) ) {
			return false;
		}

		// A test-drive account is a trial of WooPayments, not real usage of it.
		if ( ! empty( $account['is_test_drive'] ) ) {
			return false;
		}

		// Sandbox accounts don't count either.
		// Both flags are only acted upon when present: cached account data written by
		// older WooPayments versions may not carry them, and we'd rather keep counting
		// those stores as WooPayments users than reclassify them on missing data.
		if ( isset( $account['is_live'] ) && ! $account['is_live'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the store has any paid orders.
	 *
	 * Currently, we look at the past 90 days and only consider orders
	 * with status `wc-completed`, `wc-processing`, or `wc-refunded`.
	 *
	 * @return boolean Whether the store has any paid orders.
	 */
	private function has_orders(): bool {
		// First, get the stored value, if it exists.
		// This way we avoid costly DB queries and API calls.
		$has_orders = get_transient( $this->store_has_orders_transient_name );
		if ( false !== $has_orders ) {
			return filter_var( $has_orders, FILTER_VALIDATE_BOOLEAN );
		}

		// We need to determine the value.
		// Start with the assumption that the store doesn't have orders in the timeframe we look at.
		$has_orders = false;
		// By default, we will check for new orders every 6 hours.
		$expiration = 6 * HOUR_IN_SECONDS;

		// Get the latest completed, processing, or refunded order.
		$latest_order = wc_get_orders(
			array(
				'status'  => array( OrderInternalStatus::COMPLETED, OrderInternalStatus::PROCESSING, OrderInternalStatus::REFUNDED ),
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
		if ( ! empty( $latest_order ) ) {
			$latest_order = reset( $latest_order );
			// If the latest order is within the timeframe we look at, we consider the store to have orders.
			// Otherwise, it clearly doesn't have orders.
			if ( $latest_order instanceof WC_Abstract_Order
				&& strtotime( (string) $latest_order->get_date_created() ) >= strtotime( '-90 days' ) ) {

				$has_orders = true;

				// For ultimate efficiency, we will check again after 90 days from the latest order
				// because in all that time we will consider the store to have orders regardless of new orders.
				$expiration = strtotime( (string) $latest_order->get_date_created() ) + 90 * DAY_IN_SECONDS - time();
			}
		}

		// Store the value for future use.
		set_transient( $this->store_has_orders_transient_name, $has_orders ? 'yes' : 'no', $expiration );

		return $has_orders;
	}

	/**
	 * Check if the store has at least one enabled payment gateway.
	 *
	 * @return boolean Whether the store has any enabled payment gateways.
	 */
	private function has_enabled_payment_gateways(): bool {
		$payment_gateways = WC()->payment_gateways()->payment_gateways;
		if ( empty( $payment_gateways ) || ! is_array( $payment_gateways ) ) {
			return false;
		}

		foreach ( $payment_gateways as $payment_gateway ) {
			if ( filter_var( $payment_gateway->enabled, FILTER_VALIDATE_BOOLEAN ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate a hash from the store context data.
	 *
	 * @param array $context The store context data.
	 *
	 * @return string The context hash.
	 */
	private function generate_context_hash( array $context ): string {
		// Include only certain entries in the context hash.
		// We need only discrete, user-interaction dependent data.
		// Entries like `active_for` have no place in the hash generation since they change automatically.
		return md5(
			wp_json_encode(
				array(
					'country'      => $context['country'] ?? '',
					'locale'       => $context['locale'] ?? '',
					'has_orders'   => $context['has_orders'] ?? false,
					'has_payments' => $context['has_payments'] ?? false,
					'has_wcpay'    => $context['has_wcpay'] ?? false,
				)
			)
		);
	}
}
