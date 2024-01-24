<?php
namespace Automattic\WooCommerce\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\DataSourcePoller;

/**
 * Specs data source poller class for WooCommerce Payment Promotion.
 */
class WCPayPromotionDataSourcePoller extends DataSourcePoller {

	const ID = 'payment_method_promotion';

	/**
	 * Default data sources array.
	 */
	const DATA_SOURCES = array(
		'https://api.npoint.io/6eed3f7093fa2ca8fd1f',
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
