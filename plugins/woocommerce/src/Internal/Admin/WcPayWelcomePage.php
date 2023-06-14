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
	const SCRIPT_NAME = 'wcpay-welcome-page-incentive';

	/**
	 * Eligible incentive for the store.
	 *
	 * @var array|null
	 */
	private $_incentive = null;

	/**
	 * WCPayWelcomePage constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_payments_welcome_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'woocommerce_admin_allowed_promo_notes', array( $this, 'allowed_promo_notes' ) );
	}

	/**
	 * Registers the WooCommerce Payments welcome page.
	 */
	public function register_payments_welcome_page() {
		global $menu;

		// Suggestions not disabled via a setting.
		if ( get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) === 'no' ) {
			return;
		}

		/**
		 * Filter allow marketplace suggestions.
		 *
		 * User can disabled all suggestions via filter.
		 *
		 * @since 3.6.0
		 */
		if ( ! apply_filters( 'woocommerce_allow_marketplace_suggestions', true ) ) {
			return;
		}

		// Promotion manually dismissed.
		if ( get_option( 'wc_calypso_bridge_payments_dismissed', 'no' ) === 'yes' ) {
			return;
		}

		// Available incentives for the store.
		if ( empty( $this->get_incentive() ) ) {
			return;
		}

		$menu_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4NTIiIGhlaWdodD0iNjg0Ij48cGF0aCBmaWxsPSIjYTJhYWIyIiBkPSJNODIgODZ2NTEyaDY4NFY4NlptMCA1OThjLTQ4IDAtODQtMzgtODQtODZWODZDLTIgMzggMzQgMCA4MiAwaDY4NGM0OCAwIDg0IDM4IDg0IDg2djUxMmMwIDQ4LTM2IDg2LTg0IDg2em0zODQtNTU2djQ0aDg2djg0SDM4MnY0NGgxMjhjMjQgMCA0MiAxOCA0MiA0MnYxMjhjMCAyNC0xOCA0Mi00MiA0MmgtNDR2NDRoLTg0di00NGgtODZ2LTg0aDE3MHYtNDRIMzM4Yy0yNCAwLTQyLTE4LTQyLTQyVjIxNGMwLTI0IDE4LTQyIDQyLTQyaDQ0di00NHoiLz48L3N2Zz4=';

		$menu_data = array(
			'id'       => 'wc-calypso-bridge-payments-welcome-page',
			'title'    => __( 'Payments', 'woocommerce' ),
			'path'     => '/wc-pay-welcome-page',
			'position' => '56',
			'nav_args' => [
				'title'        => __( 'WooCommerce Payments', 'woocommerce' ),
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
				__( 'Payments', 'woocommerce' ),
				__( 'Payments', 'woocommerce' ),
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
	 * Enqueues scripts to preload incentive data, only on `wc-pay-welcome-page`.
	 *
	 * @return void
	 */
	public function enqueue_scripts( ) {
		// Return early if not on WCPay welcome page.
		if ( ! PageController::is_admin_page() || ! isset( $_GET['path'] ) || '/wc-pay-welcome-page' !== esc_url_raw( wp_unslash( $_GET['path'] ) ) ) {
			return;
		}

		// Return early if there is no eligible incentive.
		if ( empty( $this->get_incentive() ) ) {
			return;
		}

		wp_register_script( self::SCRIPT_NAME, '', [], WC_VERSION, false );
		wp_localize_script(
			self::SCRIPT_NAME,
			'wcpayWelcomePageIncentive',
			$this->get_incentive()
		);
		wp_enqueue_script( self::SCRIPT_NAME );
	}

	/**
	 * Adds allowed promo notes from WCPay incentive.
	 *
	 * @param array $promo_notes Allowed promo notes.
	 * @return array
	 */
	public function allowed_promo_notes( $promo_notes = [] ) {
		if ( self::get_incentive() ) {
			$promo_notes[] = self::get_incentive()['id'];
		}

		return $promo_notes;
	}

	/**
	 * Whether WCPay is installed or an account exists.
	 * By checking account data cache.
	 *
	 * @return boolean
	 */
	private function has_wcpay(): bool {
		if (WooCommercePayments::is_installed()) return true;

		$account_data = get_option( 'wcpay_account_data' );
		return isset( $account_data['data'] ) && is_array( $account_data['data'] ) && ! empty( $account_data['data'] );
	}

	/**
	 * Fetches and cache eligible incentive from WCPay API.
	 *
	 * @return array|null Array of eligible incentive or null.
	 */
	private function get_incentive() {
		// Return local cached incentive if it exists.
		if ( isset( $this->_incentive ) ) {
			return $this->_incentive;
		}

		// Return transient cached incentive if it exists.
		if ( false !== get_transient( self::TRANSIENT_NAME ) ) {
			return get_transient( self::TRANSIENT_NAME );
		}

		// Request incentive from WCPAY API.
		$url = add_query_arg(
			[
				// Store ISO-2 country code, e.g. `US`.
				'country'      => WC()->countries->get_base_country(),
				// Store locale, e.g. `en_US`.
				'locale'       => get_locale(),
				// WooCommerce install timestamp.
				'active_for'   => get_option( WCAdminHelper::WC_ADMIN_TIMESTAMP_OPTION ),
				// Whether the store has completed orders.
				'has_orders'   => ! empty(
					wc_get_orders(
						[
							'status' => [ 'wc-completed' ],
							'return' => 'ids',
							'limit'  => 1,
						]
					)
				),
				// Whether the store has at least one payment gateways enabled.
				'has_payments' => ! empty( WC()->payment_gateways()->get_available_payment_gateways() ),
				// Whether the store has a WooCommerce Payments account or it's installed.
				'has_wcpay'    => $this->has_wcpay(),
			],
			'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives',
		);

		$response = wp_remote_get( $url );

		// Return early if there is an error, waiting 6h before the next attempt.
		if ( is_wp_error( $response ) ) {
			set_transient( self::TRANSIENT_NAME, null, HOUR_IN_SECONDS * 6 );
			return;
		}

		$cache_for = wp_remote_retrieve_header( $response, 'cache-for' );
		$incentive = null;

		if (200 === wp_remote_retrieve_response_code( $response )) {
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
			$incentive = empty($incentive) ? null : $incentive;
		}


		// Store incentive in local cache.
		$this->_incentive = $incentive;

		// Skip transient cache if `cache-for` header equals zero.
		if ( '0' === $cache_for ) {
			return $incentive;
		}

		// Store incentive in transient cache for the given seconds or 24h.
		set_transient( self::TRANSIENT_NAME, $incentive, !empty($cache_for) ? (int) $cache_for : DAY_IN_SECONDS );

		return $incentive;
	}
}
