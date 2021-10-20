<?php

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

use Automattic\WooCommerce\Admin\DataSourcePoller;

/**
 * Specs data source poller class for payment gateway suggestions.
 */
class PaymentGatewaySuggestionsDataSourcePoller extends DataSourcePoller {

	const ID = 'payment_gateway_suggestions';

	/**
	 * Default data sources array.
	 */
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/suggestions.json',
	);

	/**
	 * Class instance.
	 *
	 * @var Analytics instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self( self::ID, self::DATA_SOURCES );
		}
		return self::$instance;
	}
}
