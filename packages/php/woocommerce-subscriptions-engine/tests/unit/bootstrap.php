<?php
/**
 * Unit-test bootstrap for the WooCommerce Subscriptions Engine.
 *
 * Deliberately autoloader-only: it defines ABSPATH so the `defined( 'ABSPATH' )
 * || exit;` guards pass, but it stubs NO WordPress functions. The Core zone is
 * WordPress-free, so its classes must load and run here with nothing but the
 * Composer autoloader. If a Core class ever reaches for a WP/Woo symbol, these
 * tests fatal - which is the executable form of the zoning rule.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

// Satisfy the file-access guards without providing any WordPress behavior.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/../../vendor/autoload.php';
