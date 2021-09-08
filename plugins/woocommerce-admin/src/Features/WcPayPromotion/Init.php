<?php
/**
 * Handles wcpay promotion
 */

namespace Automattic\WooCommerce\Admin\Features\WcPayPromotion;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\PaymentPlugins;

/**
 * WC Pay Promotion engine.
 */
class Init {
	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once __DIR__ . '/WCPaymentGatewayPreInstallWCPayPromotion.php';

		add_filter( PaymentPlugins::FILTER_NAME, array( __CLASS__, 'possibly_filter_recommended_payment_gateways' ) );

		if ( ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] || ! isset( $_GET['tab'] ) || 'checkout' !== $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'possibly_register_pre_install_wc_pay_promotion_gateway' ) );
		add_filter( 'option_woocommerce_gateway_order', [ __CLASS__, 'set_gateway_top_of_list' ] );
		add_filter( 'default_option_woocommerce_gateway_order', [ __CLASS__, 'set_gateway_top_of_list' ] );

		$rtl = is_rtl() ? '.rtl' : '';

		wp_enqueue_style(
			'wc-admin-wc-pay-payments-promotion',
			Loader::get_url( "wc-pay-payments-promotion/style{$rtl}", 'css' ),
			array( 'wp-components' ),
			Loader::get_file_version( 'css' )
		);

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'wc-pay-payments-promotion' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'wc-admin-wc-pay-payments-promotion',
			Loader::get_url( 'wp-admin-scripts/wc-pay-payments-promotion', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			Loader::get_file_version( 'js' ),
			true
		);
	}

	/**
	 * Possibly registers the pre install wc pay promoted gateway.
	 *
	 * @param array $gateways list of gateway classes.
	 * @return array list of gateway classes.
	 */
	public static function possibly_register_pre_install_wc_pay_promotion_gateway( $gateways ) {
		if ( self::should_register_pre_install_wc_pay_promoted_gateway() ) {
			$gateways[] = 'Automattic\WooCommerce\Admin\Features\WCPayPromotion\WCPaymentGatewayPreInstallWCPayPromotion';
		}
		return $gateways;
	}

	/**
	 * Possibly filters out woocommerce-payments from recommended payment methods.
	 *
	 * @param array $payment_methods list of payment methods.
	 * @return array list of payment method.
	 */
	public static function possibly_filter_recommended_payment_gateways( $payment_methods ) {
		if ( self::should_register_pre_install_wc_pay_promoted_gateway() ) {
			return array_filter(
				$payment_methods,
				function( $payment_method ) {
					return 'woocommerce-payments' !== $payment_method['product'];
				}
			);
		}
		return $payment_methods;
	}

	/**
	 * Checks if promoted gateway should be registered.
	 *
	 * @return boolean if promoted gateway should be registered.
	 */
	public static function should_register_pre_install_wc_pay_promoted_gateway() {
		// Check if WC Pay is enabled.
		if ( class_exists( '\WC_Payments' ) ) {
			return false;
		}
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * By default, new payment gateways are put at the bottom of the list on the admin "Payments" settings screen.
	 * For visibility, we want WooCommerce Payments to be at the top of the list.
	 *
	 * @param array $ordering Existing ordering of the payment gateways.
	 *
	 * @return array Modified ordering.
	 */
	public static function set_gateway_top_of_list( $ordering ) {
		$ordering = (array) $ordering;
		$id       = WCPaymentGatewayPreInstallWCPayPromotion::GATEWAY_ID;
		// Only tweak the ordering if the list hasn't been reordered with WooCommerce Payments in it already.
		if ( ! isset( $ordering[ $id ] ) || ! is_numeric( $ordering[ $id ] ) ) {
			$is_empty        = empty( $ordering ) || empty( $ordering[0] );
			$ordering[ $id ] = $is_empty ? 0 : ( min( $ordering ) - 1 );
		}
		return $ordering;
	}
}

