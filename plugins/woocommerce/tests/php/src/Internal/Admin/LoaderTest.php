<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Loader;
use WC_Unit_Test_Case;

/**
 * Loader Test.
 *
 * @class Loader
 */
class LoaderTest extends WC_Unit_Test_Case {
	/**
	 * Test the status fetching methods
	 */
	public function test_deprecated_statuses_fetch_methods() {
		$this->assertSame( array(), Loader::get_order_statuses( array() ) );
		$this->assertSame( array(), Loader::get_unregistered_order_statuses() );
	}

	/**
	 * Adds a deprecated function to the list of caught deprecated calls.
	 *
	 * @param string $function_name The deprecated function.
	 * @param string $replacement   The function that should have been called.
	 * @param string $version       The version of WordPress that deprecated the function.
	 * @param string $message       Optional. A message regarding the change.
	 */
	public function deprecated_function_run( $function_name, $replacement, $version, $message = '' ) {
		// We are expecting deprecations, so let's ignore them to let tests run.
	}
}
