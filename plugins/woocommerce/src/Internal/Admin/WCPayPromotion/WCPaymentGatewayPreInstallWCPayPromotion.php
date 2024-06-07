<?php
/**
 * Class WCPaymentGatewayPreInstallWCPayPromotion
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Internal\Admin\WCPayPromotion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A Psuedo WCPay gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WCPaymentGatewayPreInstallWCPayPromotion extends \WC_Payment_Gateway {

	const GATEWAY_ID = 'pre_install_woocommerce_payments_promotion';

	/**
	 * Constructor
	 */
	public function __construct() {
		$wc_pay_spec = Init::get_wc_pay_promotion_spec();
		if ( ! $wc_pay_spec ) {
			return;
		}
		$this->id           = static::GATEWAY_ID;
		$this->method_title = $wc_pay_spec->title;
		if ( property_exists( $wc_pay_spec, 'sub_title' ) ) {
			$this->title = sprintf( '<span class="gateway-subtitle" >%s</span>', $wc_pay_spec->sub_title );
		}
		$this->method_description = $wc_pay_spec->content;
		$this->has_fields         = false;

		// Set the promotion pseudo-gateway support features.
		// If the promotion spec provides the supports property, use it.
		if ( property_exists( $wc_pay_spec, 'supports' ) ) {
			$this->supports = $wc_pay_spec->supports;
		} else {
			// Otherwise, use the default supported features in line with WooPayments ones.
			// We include all features here, even if some of them are behind settings, since this is for info only.
			$this->supports = array(
				// Regular features.
				'products',
				'refunds',
				// Subscriptions features.
				'subscriptions',
				'multiple_subscriptions',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change_admin',
				'subscription_payment_method_change_customer',
				'subscription_payment_method_change',
				// Saved cards features.
				'tokenization',
				'add_payment_method',
			);
		}

		// Get setting values.
		$this->enabled = false;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'is_dismissed' => array(
				'title'   => __( 'Dismiss', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Dismiss the gateway', 'woocommerce' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Check if the promotional gateaway has been dismissed.
	 *
	 * @return bool
	 */
	public static function is_dismissed() {
		$settings = get_option( 'woocommerce_' . self::GATEWAY_ID . '_settings', array() );
		return isset( $settings['is_dismissed'] ) && 'yes' === $settings['is_dismissed'];
	}
}
