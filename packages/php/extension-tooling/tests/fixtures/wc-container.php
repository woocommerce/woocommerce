<?php
/**
 * Install the WooCommerce container fixtures.
 *
 * @package WooCommerce\ExtensionTools
 */

/**
 * Return the active WooCommerce container.
 *
 * @return object
 */
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

require __DIR__ . '/class-testbootstrapcontainer.php';
require __DIR__ . '/class-testbootstraptestingcontainer.php';

class_alias( TestBootstrapContainer::class, 'Automattic\WooCommerce\Container' );
class_alias( TestBootstrapTestingContainer::class, 'Automattic\WooCommerce\Testing\Tools\TestingContainer' );
