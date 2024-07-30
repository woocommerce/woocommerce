<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * A collection of tests for the array utility class.
 */
class ArrayUtilTest extends \WC_Unit_Test_Case {

	public function test_wordpress_version() {
		// Get the WordPress version.
		$wp_version = get_bloginfo( 'version' );

		// Print the WordPress version.
		echo 'WordPress Version: ' . $wp_version . "\n";

		// Ensure that the version is a non-empty string.
		$this->assertNotEmpty( $wp_version, 'WordPress version should not be empty.' );
	}
}
