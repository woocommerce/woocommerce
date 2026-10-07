<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use WC_Unit_Test_Case;

/**
 * Tests for the WCAdminAssets class.
 */
class WCAdminAssetsTest extends WC_Unit_Test_Case {

	/**
	 * Path of the temporary minified JS file created by a test, if any.
	 *
	 * @var string|null
	 */
	private $temp_min_file = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_admin_features', array( $this, 'enable_minified_js_feature' ), 20 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_minified_js_feature' ), 20 );

		if ( $this->temp_min_file && file_exists( $this->temp_min_file ) ) {
			wp_delete_file( $this->temp_min_file );
			$this->temp_min_file = null;
		}

		parent::tearDown();
	}

	/**
	 * Filter callback to enable the minified-js feature.
	 *
	 * @param array $features Array of active features.
	 * @return array
	 */
	public function enable_minified_js_feature( $features ) {
		return array_merge( $features, array( 'minified-js' ) );
	}

	/**
	 * Skips the test when SCRIPT_DEBUG is on, as the minified file is never served then and the test would pass vacuously.
	 */
	private function skip_if_script_debug(): void {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$this->markTestSkipped( 'Minified files are never served when SCRIPT_DEBUG is on.' );
		}
	}

	/**
	 * @testdox get_url should not use the .min suffix when the minified file does not exist, even with the minified-js feature enabled.
	 */
	public function test_get_url_falls_back_to_unminified_file_when_minified_file_is_missing(): void {
		$this->skip_if_script_debug();

		$url = WCAdminAssets::get_url( 'nonexistent-test-script/index', 'js' );

		$this->assertStringEndsWith(
			'nonexistent-test-script/index.js',
			$url,
			'The unminified file name should be served when no minified file exists.'
		);
	}

	/**
	 * @testdox get_url should use the .min suffix when the minified file exists, the minified-js feature is enabled and SCRIPT_DEBUG is off.
	 */
	public function test_get_url_uses_minified_file_when_it_exists(): void {
		$this->skip_if_script_debug();

		$dist_js_path = WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER;
		if ( ! wp_is_writable( $dist_js_path ) ) {
			$this->markTestSkipped( 'The admin dist JS folder is not writable.' );
		}

		$this->temp_min_file = $dist_js_path . 'wc-admin-assets-test.min.js';
		file_put_contents( $this->temp_min_file, '/* test */' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$url = WCAdminAssets::get_url( 'wc-admin-assets-test', 'js' );

		$this->assertStringEndsWith(
			'wc-admin-assets-test.min.js',
			$url,
			'The minified file name should be served when the minified file exists and SCRIPT_DEBUG is off.'
		);
	}

	/**
	 * @testdox Admin styles should load without the removed admin layout stylesheet.
	 */
	public function test_admin_styles_load_without_admin_layout_stylesheet(): void {
		set_current_screen( 'dashboard' );
		wp_deregister_style( 'wc-admin-layout' );

		$sut = WCAdminAssets::get_instance();
		$sut->register_scripts();

		$this->assertFalse( wp_style_is( 'wc-admin-layout', 'registered' ), 'The empty admin layout stylesheet should no longer be registered.' );

		ob_start();
		wp_styles()->do_items( array( WC_ADMIN_APP ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( WC_ADMIN_APP . '-css', $output, 'The admin stylesheet should still load with all dependencies resolved.' );
		$this->assertStringNotContainsString( 'wc-admin-layout-css', $output, 'The removed stylesheet should not be printed.' );
	}
}
