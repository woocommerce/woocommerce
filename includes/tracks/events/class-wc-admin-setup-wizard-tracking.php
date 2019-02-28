<?php
/**
 * WooCommerce Admin Setup Wizard Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of the WooCommerce Onboarding Wizard.
 */
class WC_Admin_Setup_Wizard_Tracking {
	/**
	 * Init tracking.
	 */
	public static function init() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) { // WPCS: CSRF ok, input var ok.
			return;
		}

		self::add_step_save_events();
	}

	/**
	 * Track various events when a step is saved.
	 */
	public static function add_step_save_events() {
		if ( empty( $_POST['save_step'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		$current_step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		switch ( $current_step ) {
			case '':
			case 'store_setup':
				add_action( 'admin_init', array( __CLASS__, 'track_store_setup' ), 1 );
				break;
			case 'payment':
				add_action( 'admin_init', array( __CLASS__, 'track_payments' ), 1 );
				break;
		}
	}

	/**
	 * Track store setup and store properties on save.
	 *
	 * @return void
	 */
	public static function track_store_setup() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		$properties = array(
			'country'        => isset( $_POST['store_country'] ) ? sanitize_text_field( $_POST['store_country'] ) : '',
			'currency_code'  => isset( $_POST['currency_code'] ) ? sanitize_text_field( $_POST['currency_code'] ) : '',
			'product_type'   => isset( $_POST['product_type'] ) ? sanitize_text_field( $_POST['product_type'] ) : '',
			'sell_in_person' => isset( $_POST['sell_in_person'] ) && ( 'yes' === sanitize_text_field( $_POST['sell_in_person'] ) ),
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_store_setup', $properties );
	}

	/**
	 * Track payment gateways selected.
	 *
	 * @return void
	 */
	public static function track_payments() {
		$selected_gateways     = array();
		$created_accounts      = array();
		$wc_admin_setup_wizard = new WC_Admin_Setup_Wizard();
		$gateways              = array_merge( $wc_admin_setup_wizard->get_wizard_in_cart_payment_gateways(), $wc_admin_setup_wizard->get_wizard_manual_payment_gateways() );

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( ! empty( $_POST[ 'wc-wizard-service-' . $gateway_id . '-enabled' ] ) ) { // WPCS: CSRF ok, input var ok.
				$selected_gateways[] = $gateway_id;
			}
		}

		// Stripe account being created.
		if (
			! empty( $_POST['wc-wizard-service-stripe-enabled'] ) && // WPCS: CSRF ok, input var ok.
			! empty( $_POST['stripe_create_account'] ) // WPCS: CSRF ok, input var ok.
		) {
			$created_accounts[] = 'stripe';
		}
		// PayPal account being created.
		if (
			! empty( $_POST['wc-wizard-service-ppec_paypal-enabled'] ) && // WPCS: CSRF ok, input var ok.
			! empty( $_POST['ppec_paypal_reroute_requests'] ) // WPCS: CSRF ok, input var ok.
		) {
			$created_accounts[] = 'ppec_paypal';
		}

		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
		$properties = array(
			'selected_gateways' => implode( ', ', $selected_gateways ),
			'created_accounts'  => implode( ', ', $created_accounts ),
		);
		// phpcs:enable

		WC_Tracks::record_event( 'obw_payments', $properties );
	}
}
