<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Admin\PageController;
use WC_Abstract_Order;

/**
 * Class WCPayWelcomePage
 *
 * @package Automattic\WooCommerce\Admin\Features
 */
class WcPayWelcomePage {
	const CACHE_TRANSIENT_NAME      = 'wcpay_welcome_page_incentive';
	const HAS_ORDERS_TRANSIENT_NAME = 'wcpay_incentive_store_has_orders';
	const HAD_WCPAY_OPTION_NAME     = 'wcpay_was_in_use';

	/**
	 * Plugin instance.
	 *
	 * @var WcPayWelcomePage
	 */
	protected static $instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;

		return self::$instance;
	}

	/**
	 * Eligible incentive for the store.
	 *
	 * @var array|null
	 */
	private $incentive = null;

	/**
	 * WCPayWelcomePage constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_payments_welcome_page' ] );
		add_filter( 'woocommerce_admin_shared_settings', [ $this, 'shared_settings' ] );
		add_filter( 'woocommerce_admin_allowed_promo_notes', [ $this, 'allowed_promo_notes' ] );
		add_filter( 'woocommerce_admin_woopayments_onboarding_task_badge', [ $this, 'onboarding_task_badge' ] );
	}

	/**
	 * Whether the WooPayments welcome page should be visible.
	 *
	 * @return boolean
	 */
	public function must_be_visible(): bool {
		// The WooPayments plugin must not be active.
		if ( $this->is_wcpay_active() ) {
			return false;
		}

		// Suggestions not disabled via a setting.
		if ( get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) === 'no' ) {
			return false;
		}

		/**
		 * Filter allow marketplace suggestions.
		 *
		 * User can disable all suggestions via filter.
		 *
		 * @since 3.6.0
		 */
		if ( ! apply_filters( 'woocommerce_allow_marketplace_suggestions', true ) ) {
			return false;
		}

		// An incentive must be available.
		if ( empty( $this->get_incentive() ) ) {
			return false;
		}

		// Incentive not manually dismissed.
		if ( $this->is_incentive_dismissed() ) {
			return false;
		}

		return true;
	}

	/**
	 * Registers the WooPayments welcome page.
	 */
	public function register_payments_welcome_page() {
		global $menu;

		if ( ! $this->must_be_visible() ) {
			return;
		}

		$menu_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4NTIiIGhlaWdodD0iNjg0Ij48cGF0aCBmaWxsPSIjYTJhYWIyIiBkPSJNODIgODZ2NTEyaDY4NFY4NlptMCA1OThjLTQ4IDAtODQtMzgtODQtODZWODZDLTIgMzggMzQgMCA4MiAwaDY4NGM0OCAwIDg0IDM4IDg0IDg2djUxMmMwIDQ4LTM2IDg2LTg0IDg2em0zODQtNTU2djQ0aDg2djg0SDM4MnY0NGgxMjhjMjQgMCA0MiAxOCA0MiA0MnYxMjhjMCAyNC0xOCA0Mi00MiA0MmgtNDR2NDRoLTg0di00NGgtODZ2LTg0aDE3MHYtNDRIMzM4Yy0yNCAwLTQyLTE4LTQyLTQyVjIxNGMwLTI0IDE4LTQyIDQyLTQyaDQ0di00NHoiLz48L3N2Zz4=';

		$menu_data = [
			'id'       => 'wc-calypso-bridge-payments-welcome-page',
			'title'    => esc_html__( 'Payments', 'woocommerce' ),
			'path'     => '/wc-pay-welcome-page',
			'position' => '56',
			'nav_args' => [
				'title'        => esc_html__( 'WooPayments', 'woocommerce' ),
				'is_category'  => false,
				'menuId'       => 'plugins',
				'is_top_level' => true,
			],
			'icon'     => $menu_icon,
		];

		wc_admin_register_page( $menu_data );

		// Registering a top level menu via wc_admin_register_page doesn't work when the new
		// nav is enabled. The new nav disabled everything, except the 'WooCommerce' menu.
		// We need to register this menu via add_menu_page so that it doesn't become a child of
		// WooCommerce menu.
		if ( get_option( 'woocommerce_navigation_enabled', 'no' ) === 'yes' ) {
			$menu_with_nav_data = [
				esc_html__( 'Payments', 'woocommerce' ),
				esc_html__( 'Payments', 'woocommerce' ),
				'view_woocommerce_reports',
				'admin.php?page=wc-admin&path=/wc-pay-welcome-page',
				null,
				$menu_icon,
				56,
			];

			call_user_func_array( 'add_menu_page', $menu_with_nav_data );
		}

		// Add badge.
		$badge = ' <span class="wcpay-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>';
		foreach ( $menu as $index => $menu_item ) {
			// Only add the badge markup if not already present and the menu item is the WooPayments menu item.
			if ( false === strpos( $menu_item[0], $badge )
				&& ( 'wc-admin&path=/wc-pay-welcome-page' === $menu_item[2]
					|| 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' === $menu_item[2] )
			) {
				$menu[ $index ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

				// One menu item with a badge is more than enough.
				break;
			}
		}
	}

	/**
	 * Adds shared settings for the WooPayments incentive.
	 *
	 * @param array $settings Shared settings.
	 * @return array
	 */
	public function shared_settings( $settings ): array {
		// Return early if not on a wc-admin powered page.
		if ( ! PageController::is_admin_page() ) {
			return $settings;
		}

		// Return early if the incentive must not be visible.
		if ( ! $this->must_be_visible() ) {
			return $settings;
		}

		$settings['wcpayWelcomePageIncentive'] = $this->get_incentive();

		return $settings;
	}

	/**
	 * Adds allowed promo notes from the WooPayments incentive.
	 *
	 * @param array $promo_notes Allowed promo notes.
	 * @return array
	 */
	public function allowed_promo_notes( $promo_notes = [] ): array {
		// Return early if the incentive must not be visible.
		if ( ! $this->must_be_visible() ) {
			return $promo_notes;
		}

		// Add our incentive ID to the promo notes.
		$promo_notes[] = $this->get_incentive()['id'];

		return $promo_notes;
	}

	/**
	 * Adds the WooPayments incentive badge to the onboarding task.
	 *
	 * @param string $badge Current badge.
	 *
	 * @return string
	 */
	public function onboarding_task_badge( string $badge ): string {
		// Return early if the incentive must not be visible.
		if ( ! $this->must_be_visible() ) {
			return $badge;
		}

		return $this->get_incentive()['task_badge'] ?? $badge;
	}

	/**
	 * Check if the WooPayments payment gateway is active and set up or was at some point,
	 * or there are orders processed with it, at some moment.
	 *
	 * @return boolean
	 */
	private function has_wcpay(): bool {
		// First, get the stored value, if it exists.
		// This way we avoid costly DB queries and API calls.
		// Basically, we only want to know if WooPayments was in use in the past.
		// Since the past can't be changed, neither can this value.
		$had_wcpay = get_option( self::HAD_WCPAY_OPTION_NAME );
		if ( false !== $had_wcpay ) {
			return $had_wcpay === 'yes';
		}

		// We need to determine the value.
		// Start with the assumption that the store didn't have WooPayments in use.
		$had_wcpay = false;

		// We consider the store to have WooPayments if there is meaningful account data in the WooPayments account cache.
		// This implies that WooPayments was active at some point and that it was connected.
		// If WooPayments is active right now, we will not get to this point since the plugin is active check is done first.
		if ( $this->has_wcpay_account_data() ) {
			$had_wcpay = true;
		}

		// If there is at least one order processed with WooPayments, we consider the store to have WooPayments.
		if ( false === $had_wcpay && ! empty(
			wc_get_orders(
				[
					'payment_method' => 'woocommerce_payments',
					'return'         => 'ids',
					'limit'          => 1,
				]
			)
		) ) {
			$had_wcpay = true;
		}

		// Store the value for future use.
		update_option( self::HAD_WCPAY_OPTION_NAME, $had_wcpay ? 'yes' : 'no' );

		return $had_wcpay;
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return boolean
	 */
	private function is_wcpay_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Check if there is meaningful data in the WooPayments account cache.
	 *
	 * @return boolean
	 */
	private function has_wcpay_account_data(): bool {
		$account_data = get_option( 'wcpay_account_data', [] );
		if ( ! empty( $account_data['data']['account_id'] ) ) {
			return true;
		}

		return false;
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
		$has_orders = get_transient( self::HAS_ORDERS_TRANSIENT_NAME );
		if ( false !== $has_orders ) {
			return 'yes' === $has_orders;
		}

		// We need to determine the value.
		// Start with the assumption that the store doesn't have orders in the timeframe we look at.
		$has_orders = false;
		// By default, we will check for new orders every 6 hours.
		$expiration = 6 * HOUR_IN_SECONDS;

		// Get the latest completed, processing, or refunded order.
		$latest_order = wc_get_orders(
			array(
				'status'  => array( 'wc-completed', 'wc-processing', 'wc-refunded' ),
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
				&& strtotime( $latest_order->get_date_created() ) >= strtotime( '-90 days' ) ) {

				$has_orders = true;

				// For ultimate efficiency, we will check again after 90 days from the latest order
				// because in all that time we will consider the store to have orders regardless of new orders.
				$expiration = strtotime( $latest_order->get_date_created() ) + 90 * DAY_IN_SECONDS - time();
			}
		}

		// Store the value for future use.
		set_transient( self::HAS_ORDERS_TRANSIENT_NAME, $has_orders ? 'yes' : 'no', $expiration );

		return $has_orders;
	}

	/**
	 * Check if the current incentive has been manually dismissed.
	 *
	 * @return boolean
	 */
	private function is_incentive_dismissed(): bool {
		$dismissed_incentives = get_option( 'wcpay_welcome_page_incentives_dismissed', [] );

		// If there are no dismissed incentives, return early.
		if ( empty( $dismissed_incentives ) ) {
			return false;
		}

		// Return early if there is no eligible incentive.
		$incentive = $this->get_incentive();
		if ( empty( $incentive ) ) {
			return true;
		}

		// Search the incentive ID in the dismissed incentives list.
		if ( in_array( $incentive['id'], $dismissed_incentives, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Fetches and caches eligible incentive from the WooPayments API.
	 *
	 * @return array|null Array of eligible incentive or null.
	 */
	private function get_incentive(): ?array {
		// Return in-memory cached incentive if it is set.
		if ( isset( $this->incentive ) ) {
			return $this->incentive;
		}

		// Get the cached data.
		$cache = get_transient( self::CACHE_TRANSIENT_NAME );

		// If the cached data is not expired and it's a WP_Error,
		// it means there was an API error previously and we should not retry just yet.
		if ( is_wp_error( $cache ) ) {
			// Initialize the in-memory cache and return it.
			$this->incentive = [];

			return $this->incentive;
		}

		// Gather the store context data.
		$store_context = [
			// Store ISO-2 country code, e.g. `US`.
			'country'      => WC()->countries->get_base_country(),
			// Store locale, e.g. `en_US`.
			'locale'       => get_locale(),
			// WooCommerce store active for duration in seconds.
			'active_for'   => WCAdminHelper::get_wcadmin_active_for_in_seconds(),
			'has_orders'   => $this->has_orders(),
			// Whether the store has at least one payment gateway enabled.
			'has_payments' => ! empty( WC()->payment_gateways()->get_available_payment_gateways() ),
			'has_wcpay'    => $this->has_wcpay(),
		];

		// Fingerprint the store context through a hash of certain entries.
		$store_context_hash = $this->generate_context_hash( $store_context );

		// Use the transient cached incentive if it exists, it is not expired,
		// and the store context hasn't changed since we last requested from the WooPayments API (based on context hash).
		if ( false !== $cache
			&& ! empty( $cache['context_hash'] ) && is_string( $cache['context_hash'] )
			&& hash_equals( $store_context_hash, $cache['context_hash'] ) ) {

			// We have a store context hash and it matches with the current context one.
			// We can use the cached incentive data.
			// Store the incentive in the in-memory cache and return it.
			$this->incentive = $cache['incentive'] ?? [];

			return $this->incentive;
		}

		// By this point, we have an expired transient or the store context has changed.
		// Query for incentives by calling the WooPayments API.
		$url = add_query_arg(
			$store_context,
			'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives',
		);

		$response = wp_remote_get(
			$url,
			[
				'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
			]
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
			set_transient( self::CACHE_TRANSIENT_NAME, $error, HOUR_IN_SECONDS * 6 );
			// Initialize the in-memory cache and return it.
			$this->incentive = [];

			return $this->incentive;
		}

		$cache_for = wp_remote_retrieve_header( $response, 'cache-for' );
		// Initialize the in-memory cache.
		$this->incentive = [];

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			// Decode the results, falling back to an empty array.
			$results = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

			// Find all `welcome_page` incentives.
			$incentives = array_filter(
				$results,
				function( $incentive ) {
					return 'welcome_page' === $incentive['type'];
				}
			);

			// Use the first found matching incentive or empty array if none was found.
			// Store incentive in the in-memory cache.
			$this->incentive = empty( $incentives ) ? [] : reset( $incentives );
		}

		// Skip transient cache if `cache-for` header equals zero.
		if ( '0' === $cache_for ) {
			// If we have a transient cache that is not expired, delete it so there are no leftovers.
			if ( false !== $cache ) {
				delete_transient( self::CACHE_TRANSIENT_NAME );
			}

			return $this->incentive;
		}

		// Store incentive in transient cache (together with the context hash) for the given number of seconds
		// or 1 day in seconds. Also attach a timestamp to the transient data so we know when we last fetched.
		set_transient(
			self::CACHE_TRANSIENT_NAME,
			[
				'incentive'    => $this->incentive,
				'context_hash' => $store_context_hash,
				'timestamp'    => time(),
			],
			! empty( $cache_for ) ? (int) $cache_for : DAY_IN_SECONDS
		);

		return $this->incentive;
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
				[
					'country'      => $context['country'] ?? '',
					'locale'       => $context['locale'] ?? '',
					'has_orders'   => $context['has_orders'] ?? false,
					'has_payments' => $context['has_payments'] ?? false,
					'has_wcpay'    => $context['has_wcpay'] ?? false,
				]
			)
		);
	}
}
