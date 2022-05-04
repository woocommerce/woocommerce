<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;

/**
 * Contains logic related to the WooCommerce → Subscriptions admin page.
 */
class WcPaySubscriptionsPage {

	/**
	 * The WCPay Subscriptions admin page ID.
	 *
	 * @var string
	 */
	private $page_id = 'woocommerce-wcpay-subscriptions';

	/**
	 * The option key used to record when a user has viewed the WCPay Subscriptions admin page.
	 *
	 * @var string
	 */
	private $user_viewed_option = 'woocommerce-wcpay-subscriptions_page_viewed';

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_subscriptions_page' ) );
		add_action( 'current_screen', array( $this, 'record_user_page_view' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Priority 50 to run after Automattic\WooCommerce\Internal\Admin\Homescreen::update_link_structure() which runs on 20.
		add_action( 'admin_menu', array( $this, 'restructure_menu_order' ), 50 );
	}

	/**
	 * Registers the WooCommerce → Subscriptions admin page.
	 */
	public function register_subscriptions_page() {
		global $submenu;

		if ( ! $this->is_store_experiment_eligible() ) {
			return;
		}

		$menu_data = array(
			'id'         => $this->page_id,
			'title'      => _x( 'Subscriptions', 'Admin menu name', 'woocommerce' ),
			'parent'     => 'woocommerce',
			'path'       => '/subscriptions',
			'capability' => 'manage_options',
		);

		wc_admin_register_page( $menu_data );

		if ( ! isset( $submenu['woocommerce'] ) || 'yes' === get_option( $this->user_viewed_option, 'no' ) ) {
			return;
		}

		// Add the "1" badge.
		foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
			if ( 'wc-admin&path=/subscriptions' === $menu_item[2] ) {
				$submenu['woocommerce'][ $key ][0] .= ' <span class="wcpay-subscriptions-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				break;
			}
		}
	}

	/**
	 * Returns true if the store is eligible for the WooCommerce subsciptions empty state experiment.
	 *
	 * @return bool
	 */
	private function is_store_experiment_eligible() {
		// Ineligible if WooCommerce Payments OR an existing subscriptions plugin is active.
		$active_plugins         = PluginsHelper::get_active_plugin_slugs();
		$plugin_ineligible_list = array(
			'woocommerce-payments',
			'woocommerce-subscriptions',
			'subscriptions-for-woocommerce',
			'subscriptions-for-woocommerce-pro',
			'yith-woocommerce-subscription',
		);
		foreach ( $plugin_ineligible_list as $plugin_slug ) {
			if ( in_array( $plugin_slug, $active_plugins, true ) ) {
				return false;
			}
		}

		// Ineligible if store address is not compatible with WCPay Subscriptions (US).
		$store_base_location = wc_get_base_location();
		if ( empty( $store_base_location['country'] ) || 'US' !== $store_base_location['country'] ) {
			return false;
		}

		// Ineligible if store has not been active for at least 6 months.
		if ( ! WCAdminHelper::is_wc_admin_active_in_date_range( 'month-6+' ) ) {
			return false;
		}

		// Ineligible if store has not had any sales in the last 30 days.
		$store_recent_sales_count = $this->get_store_recent_sales_count();
		if ( $store_recent_sales_count < 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the number of store recent sales as per the experiment eligibility criteria.
	 */
	private function get_store_recent_sales_count() {
		// Get orders that were paid in the last 30 days.
		$orders = wc_get_orders(
			array(
				'date_paid' => '>' . strtotime( '-30 days' ),
				'status'    => array( 'wc-completed' ),
			)
		);

		return count( $orders );
	}

	/**
	 * Registers when a user views the WCPay Subscriptions screen.
	 *
	 * Sets an option to prevent the notification badge from being displayed.
	 */
	public function record_user_page_view() {
		$current_page = PageController::get_instance()->get_current_page();

		if ( isset( $current_page['id'] ) && $current_page['id'] === $this->page_id ) {
			update_option( $this->user_viewed_option, 'yes' );
		}
	}

	/**
	 * Enqueues an inline script on the WooCommerce → Subscriptions admin page.
	 */
	public function enqueue_scripts() {
		$current_page = PageController::get_instance()->get_current_page();

		if ( ! isset( $current_page['id'] ) || $current_page['id'] !== $this->page_id ) {
			return;
		}

		$data = array(
			'newSubscriptionProductUrl' => add_query_arg(
				array(
					'post_type'             => 'product',
					'select_subscription'   => 'true',
					'subscription_pointers' => 'true',
				),
				admin_url( 'post-new.php' )
			),
			'onboardingUrl'             => add_query_arg(
				array(
					'wcpay-connect' => 'WC_SUBSCRIPTIONS_TABLE',
					'_wpnonce'      => wp_create_nonce( 'wcpay-connect' ),
				),
				admin_url( 'admin.php' )
			),
		);

		wp_add_inline_script( WC_ADMIN_APP, 'window.wcWcpaySubscriptions = ' . wp_json_encode( $data ), 'before' );
	}

	/**
	 * Reorders the default WC admin menu items to ensure the Subscriptions item comes after orders.
	 *
	 * @see Automattic\WooCommerce\Internal\Admin\Homescreen::update_link_structure() which this approach is based on.
	 */
	public function restructure_menu_order() {
		global $submenu;
		$wc_admin_menu           = array();
		$subscriptions_menu_item = null;

		foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
			$wc_admin_menu[ $key ] = $menu_item;

			// Add a placeholder element for the Subscriptions item after the Orders element. We'll replace it later.
			if ( 'edit.php?post_type=shop_order' === $menu_item[2] ) {
				$wc_admin_menu['wcpay-subscriptions'] = 'wcpay-subscriptions';
			}

			// Keep a record of the subscriptions item and remove it from its current place in the menu.
			if ( 'wc-admin&path=/subscriptions' === $menu_item[2] ) {
				$subscriptions_menu_item = $menu_item;
				unset( $wc_admin_menu[ $key ] );
			}
		}

		// Replace the placeholder element with the subscription menu item.
		if ( isset( $wc_admin_menu['wcpay-subscriptions'] ) && $subscriptions_menu_item ) {
			$wc_admin_menu['wcpay-subscriptions'] = $subscriptions_menu_item;
			$submenu['woocommerce']               = array_values( $wc_admin_menu ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
