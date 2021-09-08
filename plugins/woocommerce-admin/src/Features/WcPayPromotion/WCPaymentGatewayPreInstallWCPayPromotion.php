<?php
/**
 * Class WCPaymentGatewayPreInstallWCPayPromotion
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Features\WcPayPromotion;

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
		$this->id                 = static::GATEWAY_ID;
		$this->title              = __( 'WooCommerce Payments', 'woocommerce-admin' );
		$this->method_description = ''; // will be replaced by wc.com data.
		$this->has_fields         = false;

		// Get setting values.
		$this->enabled = false;
	}
}
