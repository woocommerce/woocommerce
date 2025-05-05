<?php

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Helper;

/**
 * Specs data source poller class for marketing recommendations.
 */
class MarketingRecommendationsDataSourcePoller extends DataSourcePoller {

	/**
	 * Data Source Poller ID.
	 */
	const ID = 'marketing_recommendations';

	/**
	 * Default data sources array.
	 *
	 * @deprecated since 9.5.0. Use get_data_sources() instead.
	 */
	const DATA_SOURCES = array();

	/**
	 * Class instance.
	 *
	 * @var MarketingRecommendationsDataSourcePoller instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self(
				self::ID,
				self::get_data_sources(),
				array(
					'spec_key' => 'product',
				)
			);
		}
		return self::$instance;
	}

	/**
	 * Get data sources.
	 *
	 * @return array
	 */
	public static function get_data_sources() {
		return array(
			WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/marketing-tab/1.3/recommendations.json',
		);
	}
}
