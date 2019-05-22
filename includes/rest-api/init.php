<?php
/**
 * Anonomous function used to init this version of the REST API.
 *
 * @package WooCommerce/RestApi
 */

return function() {
	require __DIR__ . '/src/RestApi.php';
	\WooCommerce\RestApi::instance()->init();
};
