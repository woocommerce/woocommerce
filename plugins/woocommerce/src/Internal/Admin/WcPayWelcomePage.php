<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Class WCPayWelcomePage
 *
 * @package Automattic\WooCommerce\Admin\Features
 */
class WcPayWelcomePage {
	const TRANSIENT_NAME = 'wcpay_welcome_page_incentive';

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
	}

	/**
	 * Whether the WooPayments welcome page should be visible.
	 *
	 * @return boolean
	 */
	public function must_be_visible(): bool {
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

		// Temporary solution until we improve the dismiss and cache logic.
		if ( false !== get_option( 'wcpay_welcome_page_incentives_dismissed', false ) ) {
			return false;
		}

		// The WooPayments plugin must not be active.
		if ( $this->is_wcpay_active() ) {
			return false;
		}

		// Incentive is available.
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
		foreach ( $menu as $index => $menu_item ) {
			if ( 'wc-admin&path=/wc-pay-welcome-page' === $menu_item[2]
					|| 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' === $menu_item[2] ) {
				//phpcs:ignore
				$menu[ $index ][0] .= ' <span class="wcpay-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>';
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
	 * Check if the WooPayments payment gateway is active and set up,
	 * or there are orders processed with it, at some moment.
	 *
	 * @return boolean
	 */
	private function has_wcpay(): bool {
		// We consider the store to have WooPayments if there is meaningful account data in the WooPayments account cache.
		// This implies that WooPayments is or was active at some point and that it was connected.
		if ( $this->has_wcpay_account_data() ) {
			return true;
		}

		// If there is at least one order processed with WooPayments, we consider the store to have WooPayments.
		if ( ! empty(
			wc_get_orders(
				[
					'payment_method' => 'woocommerce_payments',
					'return'         => 'ids',
					'limit'          => 1,
				]
			)
		) ) {
			return true;
		}

		return false;
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
	 * Check if the current incentive has been manually dismissed.
	 *
	 * @return boolean
	 */
	private function is_incentive_dismissed(): bool {
		// Return early if there is no eligible incentive.
		if ( empty( $this->get_incentive() ) ) {
			return true;
		}

		$dismissed_incentives = get_option( 'wcpay_welcome_page_incentives_dismissed', [] );

		if ( in_array( $this->get_incentive()['id'], $dismissed_incentives, true ) ) {
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

		$store_context = [
			// Store ISO-2 country code, e.g. `US`.
			'country'      => WC()->countries->get_base_country(),
			// Store locale, e.g. `en_US`.
			'locale'       => get_locale(),
			// WooCommerce active for duration in seconds.
			'active_for'   => WCAdminHelper::get_wcadmin_active_for_in_seconds(),
			// Whether the store has paid orders in the last 90 days.
			'has_orders'   => ! empty(
				wc_get_orders(
					[
						'status'       => [ 'wc-completed', 'wc-processing' ],
						'date_created' => '>=' . strtotime( '-90 days' ),
						'return'       => 'ids',
						'limit'        => 1,
					]
				)
			),
			// Whether the store has at least one payment gateway enabled.
			'has_payments' => ! empty( WC()->payment_gateways()->get_available_payment_gateways() ),
			'has_wcpay'    => $this->has_wcpay(),
		];

		// Fingerprint the store context through a hash of certain entries.
		$store_context_hash = $this->generate_context_hash( $store_context );

		// Use the transient cached incentive if it exists, it is not expired,
		// and the store context hasn't changed since we last requested from the WooPayments API (based on context hash).
		$transient_cache = get_transient( self::TRANSIENT_NAME );
		if ( false !== $transient_cache ) {
			if ( is_null( $transient_cache ) ) {
				// This means there was an error and we shouldn't retry just yet.
				// Initialize the in-memory cache.
				$this->incentive = [];
			} elseif ( ! empty( $transient_cache['context_hash'] ) && is_string( $transient_cache['context_hash'] )
				&& hash_equals( $store_context_hash, $transient_cache['context_hash'] ) ) {

				// We have a store context hash and it matches with the current context one.
				// We can use the cached incentive data.
				// Store the incentive in the in-memory cache.
				$this->incentive = $transient_cache['incentive'] ?? [];
			}

			// If the in-memory cache has been set, return it.
			if ( isset( $this->incentive ) ) {
				return $this->incentive;
			}
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
			// Store a null value in the transient so we know this is due to an API error.
			set_transient( self::TRANSIENT_NAME, null, HOUR_IN_SECONDS * 6 );
			// Initialize the in-memory cache.
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
			if ( false !== $transient_cache ) {
				delete_transient( self::TRANSIENT_NAME );
			}

			return $this->incentive;
		}

		// Store incentive in transient cache (together with the context hash) for the given number of seconds or 24h.
		set_transient(
			self::TRANSIENT_NAME,
			[
				'incentive'    => $this->incentive,
				'context_hash' => $store_context_hash,
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
