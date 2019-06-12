<?php
/**
 * Anonomous function used to init this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

return function() {
	require __DIR__ . '/src/Server.php';
	\WooCommerce\RestApi\Server::instance()->init();
};
