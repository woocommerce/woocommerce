<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

/**
 * Settings controller for Customer Stock Notifications.
 */
class SettingsController {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Add a 'Customer stock notifications' section to Products settings.
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_customer_stock_notifications_section' ), 100, 1 );

		// Add the Customer Stock Notifications settings.
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_customer_stock_notifications_settings' ), 100, 2 );

		// Display admin notices about incompatible settings combinations.
		add_action( 'admin_notices', array( $this, 'output_admin_notices' ) );
	}

	/**
	 * Add a 'Customer stock notifications' section to Products settings.
	 *
	 * @param array $sections Products settings sections.
	 * @return array New Products settings sections.
	 */
	public function add_customer_stock_notifications_section( $sections ) {
		if ( ! is_array( $sections ) ) {
			return $sections;
		}

		$section_title = __( 'Customer stock notifications', 'woocommerce' );

		// Add 'Customer stock notifications' section to the Products tab, after Inventory.
		$inventory_index = array_search( 'inventory', array_keys( $sections ), true );
		if ( false !== $inventory_index ) {
			$sections = array_slice( $sections, 0, $inventory_index + 1, true ) +
				array( 'customer_stock_notifications' => $section_title ) +
				array_slice( $sections, $inventory_index + 1, null, true );
		} else {
			$sections['customer_stock_notifications'] = $section_title;
		}

		return $sections;
	}

	/**
	 * Add the Customer Stock Notifications settings.
	 *
	 * @param array  $settings Original settings.
	 * @param string $section_id Settings section identifier.
	 * @return array New settings.
	 */
	public function add_customer_stock_notifications_settings( $settings, $section_id ) {

		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		if ( 'customer_stock_notifications' !== $section_id ) {
			return $settings;
		}

		/**
		 * Filter the Customer Stock Notifications settings.
		 *
		 * @since 0.0.0
		 *
		 * @param array $default_customer_stock_notifications_settings The default Customer Stock Notifications settings.
		 */
		$stock_notification_settings = apply_filters(
			'woocommerce_customer_stock_notifications_settings',
			array(

				array(
					'title' => __( 'Customer stock notifications', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'product_customer_stock_notifications_options',
				),

				array(
					'title'   => __( 'Allow sign-ups', 'woocommerce' ),
					'desc'    => __( 'Let customers sign up to be notified when products in your store are restocked.', 'woocommerce' ),
					'id'      => 'woocommerce_customer_stock_notifications_allow_signups',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'   => __( 'Require double opt-in to sign up', 'woocommerce' ),
					'desc'    => __( 'To complete the sign-up process, customers must follow a verification link sent to their e-mail after submitting the sign-up form.', 'woocommerce' ),
					'id'      => 'woocommerce_customer_stock_notifications_require_double_opt_in',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'           => __( 'Guest sign-up', 'woocommerce' ),
					'desc'            => __( 'Customers must be logged in to sign up for stock notifications.', 'woocommerce' ),
					'id'              => 'woocommerce_customer_stock_notifications_require_account',
					'default'         => 'no',
					'type'            => 'checkbox',
					'desc_tip'        => __( 'When enabled, guests will be redirected to a login page to complete the sign-up process.', 'woocommerce' ),
					'checkboxgroup'   => 'start',
					'hide_if_checked' => 'option',
				),

				array(
					'desc'            => __( 'Create an account when guests sign up for stock notifications.', 'woocommerce' ),
					'id'              => 'woocommerce_customer_stock_notifications_create_account_on_signup',
					'default'         => 'no',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'end',
					'hide_if_checked' => 'yes',
					'autoload'        => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'product_customer_stock_notifications_options',
				),
			)
		);

		$settings = array_merge( $settings, $stock_notification_settings );

		return $settings;
	}

	/**
	 * Display admin notices about incompatible settings combinations.
	 *
	 * @return void
	 */
	public function output_admin_notices() {
		// Only show notices on the Customer Stock Notifications settings page.
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id || ! isset( $_GET['section'] ) || 'customer_stock_notifications' !== $_GET['section'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( 'no' === get_option( 'woocommerce_registration_generate_password', 'no' ) && 'yes' === get_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'no' ) ) {
			wp_admin_notice(
				sprintf(
					/* translators: %s settings page link */
					__( 'WooCommerce is currently <a href="%s">configured</a> to create new accounts without generating passwords automatically. Guests who sign up to receive stock notifications will need to reset their password before they can log into their new account.', 'woocommerce' ),
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) )
				),
				array(
					'id'          => 'message',
					'type'        => 'warning',
					'dismissible' => false,
				)
			);
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && 'yes' === get_option( 'woocommerce_customer_stock_notifications_allow_signups' ) ) {
			wp_admin_notice(
				sprintf(
					/* translators: %s settings page link */
					__( 'WooCommerce is currently <a href="%s">configured</a> to hide out-of-stock products from your catalog. Customers will not be able sign up for back-in-stock notifications while this option is enabled.', 'woocommerce' ),
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ) )
				),
				array(
					'id'          => 'message',
					'type'        => 'warning',
					'dismissible' => false,
				)
			);
		}
	}
}
