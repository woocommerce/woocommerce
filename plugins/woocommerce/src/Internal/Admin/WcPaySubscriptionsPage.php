<?php

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use WooCommerce\Admin\Experimental_Abtest;

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
	 * The option key used to record when the user dismisses the WCPay Subscriptions page.
	 *
	 * @var string
	 */
	private $user_dismissed_option = 'woocommerce-wcpay-subscriptions_dismissed';

	/**
	 * The WooCommerce > Subscriptions menu item slug.
	 *
	 * @var string
	 */
	const SUBSCRIPTION_MENU_ITEM_SLUG = 'wc-admin&path=/subscriptions';

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

		if ( 'yes' === get_option( $this->user_dismissed_option, 'no' ) ) {
			return;
		}

		if ( 'yes' !== get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			return;
		}

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

		// translators: Admin menu item badge. It is used alongside the "Subscriptions" menu item to grab attention and let merchants know that this is a new offering.
		$new_badge_text = __( 'new', 'woocommerce' );

		// Add the "new" badge.
		foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
			if ( self::SUBSCRIPTION_MENU_ITEM_SLUG === $menu_item[2] ) {
				$submenu['woocommerce'][ $key ][0] .= sprintf( ' <span class="wcpay-subscriptions-menu-badge awaiting-mod count-1"><span class="plugin-count">%s</span></span>', esc_html( $new_badge_text ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				break;
			}
		}
	}

	/**
	 * Returns true if the store is eligible for the WooCommerce subscriptions empty state experiment.
	 *
	 * @return bool
	 */
	private function is_store_experiment_eligible() {
		// Ineligible if WooCommerce Payments OR an existing subscriptions plugin is installed.
		$installed_plugins      = PluginsHelper::get_installed_plugin_slugs();
		$plugin_ineligible_list = array(
			'woocommerce-payments',
			'woocommerce-subscriptions',
			'subscriptio',
			'subscriptions-for-woocommerce',
			'subscriptions-for-woocommerce-pro',
			'sumosubscriptions',
			'yith-woocommerce-subscription',
			'xa-woocommerce-subscriptions',
		);
		foreach ( $plugin_ineligible_list as $plugin_slug ) {
			if ( in_array( $plugin_slug, $installed_plugins, true ) ) {
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
		if ( ! $this->get_store_recent_sales_eligibility() ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns true if the store has an order that has been paid within the last 30 days.
	 *
	 * @return bool
	 */
	private function get_store_recent_sales_eligibility() {
		$transient_key = 'woocommerce-wcpay-subscriptions_recent_sales_eligibility';

		// Load from cache.
		$is_eligible_cached = get_transient( $transient_key );

		// Valid cache found.
		if ( false !== $is_eligible_cached ) {
			return wc_string_to_bool( $is_eligible_cached );
		}

		// Get a single order that has been paid within the last 30 days.
		$orders = wc_get_orders(
			array(
				'date_created' => '>' . strtotime( '-30 days' ),
				'status'       => wc_get_is_paid_statuses(),
				'limit'        => 1,
				'return'       => 'ids',
			)
		);

		$is_eligible = count( $orders ) >= 1;
		set_transient( $transient_key, wc_bool_to_string( $is_eligible ), DAY_IN_SECONDS );

		return $is_eligible;
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
	 * Enqueues an inline script on WooCommerce registered pages for use
	 * on the WooCommerce → Subscriptions admin page.
	 */
	public function enqueue_scripts() {
		if ( ! PageController::get_instance()->is_registered_page() ) {
			return;
		}

		if ( ! $this->is_store_experiment_eligible() ) {
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
			'dismissOptionKey'          => $this->user_dismissed_option,
			'noThanksUrl'               => wc_admin_url(),
			'experimentAssignment'      => $this->get_user_experiment_assignment(),
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

		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}
		foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
			$wc_admin_menu[ $key ] = $menu_item;

			// Add a placeholder element for the Subscriptions item after the Orders element. We'll replace it later.
			if ( 'edit.php?post_type=shop_order' === $menu_item[2] ) {
				$wc_admin_menu['wcpay-subscriptions'] = 'wcpay-subscriptions';
			}

			// Keep a record of the subscriptions item and remove it from its current place in the menu.
			if ( self::SUBSCRIPTION_MENU_ITEM_SLUG === $menu_item[2] ) {
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

	/**
	 * Returns the user's assignment for this experiment.
	 *
	 * @return string Either 'A' or 'B' to represent which treatment the user is assigned to.
	 */
	private function get_user_experiment_assignment() {
		$anon_id        = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking' );
		$abtest         = new Experimental_Abtest(
			$anon_id,
			'woocommerce',
			$allow_tracking
		);

		return $abtest->get_variation( 'woocommerce_wcpay_subscriptions_page_202207_v1' ) === 'control' ? 'A' : 'B';
	}
}
