<?php

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
/**
 * Specs data source poller class for remote free extensions.
 */
class RemoteFreeExtensionsDataSourcePoller extends DataSourcePoller {

	const ID = 'remote_free_extensions';

	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/obw-free-extensions/4.0/extensions.json',
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
			self::$instance = new self(
				self::ID,
				self::DATA_SOURCES,
				array(
					'spec_key' => 'key',
				)
			);
		}
		return self::$instance;
	}
}
