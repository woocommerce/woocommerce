<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;
use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * Class WCPayWelcomePage
 *
 * @package Automattic\WooCommerce\Admin\Features
 */
class WcPayWelcomePage {

	const EXPERIMENT_NAME    = 'woocommerce_payments_menu_promo_us_2022';
	const ELIGIBLE_COUNTRIES = array( 'US', 'ES' );

	/**
	 * WCPayWelcomePage constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_payments_welcome_page' ) );
	}

	/**
	 * Registers the WooCommerce Payments welcome page.
	 */
	public function register_payments_welcome_page() {
		global $menu;

		// WC Payment must not be installed.
		if ( WooCommercePayments::is_installed() ) {
			return;
		}

		// Live store for at least 90 days.
		if ( ! WCAdminHelper::is_wc_admin_active_for( DAY_IN_SECONDS * 90 ) ) {
			return;
		}

		// Business country must be in the eligible ones.
		if ( ! in_array( WC()->countries->get_base_country(), self::ELIGIBLE_COUNTRIES ) ) {
			return;
		}

		// No existing WCPay account.
		if ( $this->has_wcpay_account() ) {
			return;
		}

		// Has processed orders.
		if ( count( wc_get_orders( [ 'status' => [ 'wc-completed' ] ] ) ) < 1 ) {
			return;
		}

		// Suggestions may be disabled via a setting.
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

		// Manually dismissed.
		if ( get_option( 'wc_calypso_bridge_payments_dismissed', 'no' ) === 'yes' ) {
			return;
		}

		// Users must be in the experiment.
		if ( ! $this->is_user_in_treatment_mode() ) {
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
	 * Whether a WCPay account exists. By checking account data cache.
	 *
	 * @return boolean
	 */
	private function has_wcpay_account(): bool {
		$account_data = get_option( 'wcpay_account_data' );
		return isset( $account_data['data'] ) && is_array( $account_data['data'] ) && ! empty( $account_data['data'] );
	}

	/**
	 * Checks if user is in the experiment.
	 *
	 * @return bool Whether the user is in the treatment group.
	 */
	private function is_user_in_treatment_mode() {
		$anon_id        = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
		$allow_tracking = get_option( 'woocommerce_allow_tracking' ) === 'yes';
		$abtest         = new \WooCommerce\Admin\Experimental_Abtest(
			$anon_id,
			'woocommerce',
			$allow_tracking
		);

		return $abtest->get_variation( self::EXPERIMENT_NAME ) === 'treatment';
	}
}
