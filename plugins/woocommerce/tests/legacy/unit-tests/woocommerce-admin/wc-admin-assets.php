<?php
/**
 * Loader tests
 *
 * @package WooCommerce\Admin\Tests\Loader
 */

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * WC_Admin_Tests_Page_WCAdminAssets Class
 *
 * @package WooCommerce\Admin\Tests\WCAdminAssets
 */
class WC_Admin_Tests_WCAdminAssets extends WP_UnitTestCase {
	/**
	 * Setup
	 */
	public function setUp(): void {
		add_filter( 'woocommerce_admin_features', array( $this, 'turn_on_unminified_js_feature' ), 20, 1 );
	}

	/**
	 * Tear Down
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_admin_features', array( $this, 'turn_on_unminified_js_feature' ), 20 );
	}

	/**
	 * Fitler to enable unminified-js feature.
	 *
	 * @param  array $features Array of active features.
	 */
	public static function turn_on_unminified_js_feature( $features ) {
		return array_merge( $features, array( 'unminified-js' ) );
	}

	/**
	 * Test get_url()
	 */
	public function test_get_url() {
		$result = WCAdminAssets::get_url( 'flavortown', 'js' );

		// All we are concerned about in this test is the js filename. Pop it off the end of the asset url.
		$parts           = explode( '/', $result );
		$final_file_name = array_pop( $parts );

		// Since this can vary depending on the env the tests are running in, we will make this assertion based upon the SCRIPT_DEBUG value.
		$expected_value = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'flavortown.js' : 'flavortown.min.js';

		$this->assertEquals(
			$expected_value,
			$final_file_name,
			'the anticipated js file name should use .min when SCRIPT_DEBUG is off, and have no .min when SCRIPT_DEBUG is on.'
		);
	}

	/**
	 * Tests for should_use_minified_js_file
	 */
	public function test_should_use_minified_js_file() {
		// We will simulate a call with SCRIPT_DEBUG on.
		$script_debug = true;

		$this->assertFalse(
			WCAdminAssets::should_use_minified_js_file( $script_debug ),
			'Since unminifed js feature is TRUE/on, and script_debug is true, should_use_minified_js_file should return false'
		);

		// Now we will simulate SCRIPT_DEBUG off/false.
		$script_debug = false;
		$this->assertTrue(
			WCAdminAssets::should_use_minified_js_file( $script_debug ),
			'Since unminifed js feature is TRUE/on, and script_debug is false, should_use_minified_js_file should return true'
		);
	}
}
