<?php

namespace Automattic\WooCommerce\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;

/**
 * Specs data source poller class for WooPayments Promotion.
 */
class WCPayPromotionDataSourcePoller extends DataSourcePoller {

	const ID = 'payment_method_promotion';

	/**
	 * Default data sources array.
	 */
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/2.0/payment-method/promotions.json',
	);

	/**
	 * Class instance.
	 *
	 * @var WCPayPromotionDataSourcePoller instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			// Add country query param to data sources.
			$base_location = wc_get_base_location();
			$data_sources  = array_map(
				function ( $url ) use ( $base_location ) {
					return add_query_arg(
						'country',
						$base_location['country'] ?? '',
						$url
					);
				},
				self::DATA_SOURCES
			);

			self::$instance = new self( self::ID, $data_sources );
		}
		return self::$instance;
	}
}
