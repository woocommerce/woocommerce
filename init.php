<?php
/**
 * Anonomous function used to init this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

return function() {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	} else {
		require __DIR__ . '/src/Autoloader.php';
		$classmap = require 'classmap.php';
		\WooCommerce\RestApi\Autoloader::register( $classmap );
	}
	\WooCommerce\RestApi\Server::instance()->init();
};
