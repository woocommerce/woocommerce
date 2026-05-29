<?php
/**
 * Prototype bootstrap - not for merge.
 *
 * Initialises all prototype dev tools and improvements.
 * Remove this file (and the require_once in woocommerce.php) to clean up.
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Prototype\DevPanel;
use Automattic\WooCommerce\Internal\Prototype\Improvements\ReorderControls;

DevPanel::init();
ReorderControls::init();
