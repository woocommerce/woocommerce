<?php
/**
 * Listener loader.
 *
 * @package WooCommerce\UnitTests
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : sys_get_temp_dir() . '/wordpress-tests-lib';

// Polyfill a function that wasn't added until WordPress 5.1.
if ( ! function_exists( 'tests_get_phpunit_version' ) ) {
	/**
	 * Retrieves PHPUnit runner version.
	 */
	function tests_get_phpunit_version() {
		if ( class_exists( 'PHPUnit_Runner_Version' ) ) {
			$version = PHPUnit_Runner_Version::id();
		} elseif ( class_exists( 'PHPUnit\Runner\Version' ) ) {
			// Must be parsable by PHP 5.2.x.
			$version = call_user_func( 'PHPUnit\Runner\Version::id' );
		} else {
			$version = 0;
		}

		return $version;
	}
}

/**
 * The listener-loader.php file wasn't introduced into the core test framework until r44701, which
 * means it came after WordPress 5.0 (r43971).
 *
 * Once WordPress 5.0 is no longer supported, we can safely reduce this to:
 *
 *   require_once $wp_tests_dir . '/includes/listener-loader.php';
 *
 * @link https://core.trac.wordpress.org/changeset/44701/
 */
if ( file_exists( $wp_tests_dir . '/includes/listener-loader.php' ) ) {
	require_once $wp_tests_dir . '/includes/listener-loader.php';
} else {
	if ( version_compare( tests_get_phpunit_version(), '7.0', '>=' ) ) {
		require $wp_tests_dir . '/includes/phpunit7/speed-trap-listener.php';
	} else {
		require $wp_tests_dir . '/includes/speed-trap-listener.php';
	}
}
