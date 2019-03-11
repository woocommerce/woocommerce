<?php
/**
 * Load up extra automatic mappings for the CSV importer.
 *
 * @package WooCommerce\Admin\Importers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/default.php';
require __DIR__ . '/generic.php';
require __DIR__ . '/wordpress.php';
