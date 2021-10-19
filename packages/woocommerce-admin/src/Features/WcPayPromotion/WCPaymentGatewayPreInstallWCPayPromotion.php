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
		$wc_pay_spec        = Init::get_wc_pay_promotion_spec();
		$this->id           = static::GATEWAY_ID;
		$this->method_title = $wc_pay_spec->title;
		if ( property_exists( $wc_pay_spec, 'sub_title' ) ) {
			$this->title = sprintf( '<span class="gateway-subtitle" >%s</span>', $wc_pay_spec->sub_title );
		}
		$this->method_description = $wc_pay_spec->content;
		$this->has_fields         = false;

		// Get setting values.
		$this->enabled = false;
	}
}
