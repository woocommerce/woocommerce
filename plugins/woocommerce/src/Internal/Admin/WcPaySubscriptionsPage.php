<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;

/**
 * Contains logic related to the WooCommerce → Subscriptions admin page.
 */
class WcPaySubscriptionsPage {

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_subscriptions_page' ) );
	}

	/**
	 * Registers the WooCommerce → Subscriptions admin page.
	 */
	public function register_subscriptions_page() {
		global $submenu;

		// WC Payment must not be active.
		if ( is_plugin_active( 'woocommerce-payments/woocommerce-payments.php' ) ) {
			return;
		}

		if ( ! WooCommercePayments::is_supported() ) {
			return;
		}

		$menu_data = array(
			'id'         => 'woocommerce-wcpay-subscriptions',
			'title'      => _x( 'Subscriptions', 'Admin menu name', 'woocommerce' ),
			'parent'     => 'woocommerce',
			'path'       => '/subscriptions',
			'capability' => 'manage_options',
			'nav_args' => [
				'order'  => 10, // TODO: this menu item should appear below the "Orders" menu item.
				'parent' => 'woocommerce',
			],
		);

		wc_admin_register_page( $menu_data );

		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		// Add the "1" badge.
		// TODO: remove this badge after the user has visited the "Subscriptions" page the first time.
		foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
			if ( 'wc-admin&path=/subscriptions' === $menu_item[2] ) {
				$submenu['woocommerce'][ $key ][0] .= ' <span class="wcpay-subscriptions-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>';
				break;
			}
		}
	}
}
