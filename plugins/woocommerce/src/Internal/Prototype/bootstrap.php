<?php
/**
 * Prototype bootstrap - not for merge.
 *
 * Initialises all prototype dev tools and improvements.
 * Remove this file (and the require_once in woocommerce.php) to clean up.
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Prototype\DevPanel;
use Automattic\WooCommerce\Internal\Prototype\Improvements\IconModernisation;
use Automattic\WooCommerce\Internal\Prototype\Improvements\MaxWidth;
use Automattic\WooCommerce\Internal\Prototype\Improvements\ReorderControls;
use Automattic\WooCommerce\Internal\Prototype\Improvements\SavePublishClarity;
use Automattic\WooCommerce\Internal\Prototype\Improvements\TypographyHierarchy;

DevPanel::init();
ReorderControls::init();
IconModernisation::init();
MaxWidth::init();
SavePublishClarity::init();
TypographyHierarchy::init();
