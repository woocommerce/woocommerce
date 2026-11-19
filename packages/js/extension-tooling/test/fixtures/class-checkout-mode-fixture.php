<?php
/**
 * Exercise checkout switching with the options and theme APIs stubbed.
 *
 * @package WooCommerce\ExtensionTooling
 */

use Automattic\WooCommerce\ExtensionTooling\Checkout_Mode;

require dirname( __DIR__, 2 ) . '/bin/checkout-mode.php';

/** Test-only checkout fixture. */
class Checkout_Mode_Fixture {
	use Checkout_Mode;

	const THEME_LEGACY = 'storefront';
	const THEME_BLOCKS = 'twentytwentyfive';
}

$options = array();
$theme   = 'storefront';
$changes = array();

/** Stub get_option. */
function get_option( $name, $default_value = false ) {
	global $options;
	return $options[ $name ] ?? $default_value;
}

/** Stub update_option. */
function update_option( $name, $value ) {
	global $options;
	$options[ $name ] = $value;
}

/** Stub get_stylesheet. */
function get_stylesheet() {
	global $theme;
	return $theme;
}

/** Stub switch_theme. */
function switch_theme( $stylesheet ) {
	global $theme, $changes;
	$theme     = $stylesheet;
	$changes[] = $stylesheet;
}

$pages = static function () {
	return array(
		Checkout_Mode_Fixture::filter_cart_page_id( '32' ),
		Checkout_Mode_Fixture::filter_checkout_page_id( '33' ),
	);
};

$unseeded = $pages();
$options  = array(
	'tests_legacy_woocommerce_cart_page_id'     => '12',
	'tests_legacy_woocommerce_checkout_page_id' => '13',
);
$initial  = $pages();
Checkout_Mode_Fixture::switch_test_mode( 'blocks' );
$blocks = array( get_option( 'e2e_tests_mode' ), $theme, $pages() );
Checkout_Mode_Fixture::switch_test_mode( 'blocks' );
Checkout_Mode_Fixture::switch_test_mode( 'legacy' );
$legacy = array( get_option( 'e2e_tests_mode' ), $theme, $pages() );
Checkout_Mode_Fixture::switch_test_mode( 'unknown' );
$fallback = array( get_option( 'e2e_tests_mode' ), $theme );

echo json_encode( array( $unseeded, $initial, $blocks, $legacy, $fallback, $changes ) );
