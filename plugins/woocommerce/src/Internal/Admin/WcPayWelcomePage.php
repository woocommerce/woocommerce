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
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'shared_settings' ) );
		add_filter( 'woocommerce_admin_allowed_promo_notes', [ $this, 'allowed_promo_notes' ] );
	}

	/**
	 * Whether the WooPayments welcome page is visible.
	 *
	 * @return boolean
	 */
	public function must_be_visible() {
		// Suggestions not disabled via a setting.
		if ( get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) === 'no' ) {
			return false;
		}

		/**
		 * Filter allow marketplace suggestions.
		 *
		 * User can disabled all suggestions via filter.
		 *
		 * @since 3.6.0
		 */
		if ( ! apply_filters( 'woocommerce_allow_marketplace_suggestions', true ) ) {
			return false;
		}

		// WCPay must not be in use or previously used.
		if ( $this->has_wcpay() ) {
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

		$menu_data = array(
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
		);

		wc_admin_register_page( $menu_data );

		// Registering a top level menu via wc_admin_register_page doesn't work when the new
		// nav is enabled. The new nav disabled everything, except the 'WooCommerce' menu.
		// We need to register this menu via add_menu_page so that it doesn't become a child of
		// WooCommerce menu.
		if ( get_option( 'woocommerce_navigation_enabled', 'no' ) === 'yes' ) {
			$menu_with_nav_data = array(
				esc_html__( 'Payments', 'woocommerce' ),
				esc_html__( 'Payments', 'woocommerce' ),
				'view_woocommerce_reports',
				'admin.php?page=wc-admin&path=/wc-pay-welcome-page',
				null,
				$menu_icon,
				56,
			);

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
	 * Adds shared settings for WCPay incentive.
	 *
	 * @param array $settings Shared settings.
	 * @return array
	 */
	public function shared_settings( $settings ) {
		// Return early if not on a wc-admin powered page.
		if ( ! PageController::is_admin_page() ) {
			return $settings;
		}

		// Return early if there is no eligible incentive.
		if ( empty( $this->get_incentive() ) ) {
			return $settings;
		}

		$settings['wcpayWelcomePageIncentive'] = $this->get_incentive();

		return $settings;
	}

	/**
	 * Adds allowed promo notes from WCPay incentive.
	 *
	 * @param array $promo_notes Allowed promo notes.
	 * @return array
	 */
	public function allowed_promo_notes( $promo_notes = [] ) {
		if ( $this->get_incentive() ) {
			$promo_notes[] = $this->get_incentive()['id'];
		}

		return $promo_notes;
	}

	/**
	 * Whether WCPay is installed, connected, an account exists, or there are orders processed with it.
	 *
	 * @return boolean
	 */
	private function has_wcpay(): bool {
		// Installed.
		if ( class_exists( '\WC_Payments' ) ) {
			return true;
		}

		// Currently connected.
		if ( WooCommercePayments::is_connected() ) {
			return true;
		}

		// Account data in cache.
		$account_data = get_option( 'wcpay_account_data' );
		if ( isset( $account_data['data'] ) && is_array( $account_data['data'] ) && ! empty( $account_data['data'] ) ) {
			return true;
		}

		// Orders processed with it.
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
	 * Whether the current incentive has been manually dismissed.
	 *
	 * @return boolean
	 */
	private function is_incentive_dismissed() {

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
	 * Fetches and cache eligible incentive from WCPay API.
	 *
	 * @return array|null Array of eligible incentive or null.
	 */
	private function get_incentive() {
		// Return local cached incentive if it exists.
		if ( isset( $this->incentive ) ) {
			return $this->incentive;
		}

		// Return transient cached incentive if it exists.
		if ( false !== get_transient( self::TRANSIENT_NAME ) ) {
			$this->incentive = get_transient( self::TRANSIENT_NAME );
			return $this->incentive;
		}

		// Request incentive from WCPAY API.
		$url = add_query_arg(
			[
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
							'status'       => array_map( 'wc_get_order_status_name', wc_get_is_paid_statuses() ),
							'date_created' => '>=' . strtotime( '-90 days' ),
							'return'       => 'ids',
							'limit'        => 1,
						]
					)
				),
				// Whether the store has at least one payment gateway enabled.
				'has_payments' => ! empty( WC()->payment_gateways()->get_available_payment_gateways() ),
				// Whether the store has WooPayments active, connected, and a WooPayments account.
				'has_wcpay'    => $this->has_wcpay(),
			],
			'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives',
		);

		$response = wp_remote_get(
			$url,
			array(
				'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
			)
		);

		// Return early if there is an error, waiting 6h before the next attempt.
		if ( is_wp_error( $response ) ) {
			set_transient( self::TRANSIENT_NAME, null, HOUR_IN_SECONDS * 6 );

			return null;
		}

		$cache_for = wp_remote_retrieve_header( $response, 'cache-for' );
		$incentive = null;

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			// Decode the results, falling back to an empty array.
			$results = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

			// Find a `welcome_page` incentive.
			$incentive = current(
				array_filter(
					$results,
					function( $incentive ) {
						return 'welcome_page' === $incentive['type'];
					}
				)
			);

			// Set incentive to null if it's empty.
			$incentive = empty( $incentive ) ? null : $incentive;
		}

		// Store incentive in local cache.
		$this->incentive = $incentive;

		// Skip transient cache if `cache-for` header equals zero.
		if ( '0' === $cache_for ) {
			return $incentive;
		}

		// Store incentive in transient cache for the given seconds or 24h.
		set_transient( self::TRANSIENT_NAME, $incentive, ! empty( $cache_for ) ? (int) $cache_for : DAY_IN_SECONDS );

		return $incentive;
	}
}
