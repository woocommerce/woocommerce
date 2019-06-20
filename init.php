<?php
/**
 * Anonomous function used to init this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

return function() {
	if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		return;
	}
	require __DIR__ . '/vendor/autoload.php';
	\WooCommerce\RestApi\Server::instance()->init();
};
