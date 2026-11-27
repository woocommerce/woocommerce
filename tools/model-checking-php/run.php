<?php
/**
 * Run the isolated PHP model-checking POC without booting WordPress.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Tools\ModelChecking\Application;

require_once __DIR__ . '/src/Model.php';
require_once __DIR__ . '/src/Explorer.php';
require_once __DIR__ . '/src/Phase.php';
require_once __DIR__ . '/examples/StockReservation.php';
require_once __DIR__ . '/src/Application.php';

exit( ( new Application() )->run( $argv ) );
