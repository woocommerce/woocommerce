<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Internal\Admin\ImportExport\TempDirectoryGuard;
use WC_Unit_Test_Case;

/**
 * Tests for the TempDirectoryGuard class.
 */
class TempDirectoryGuardTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should treat the WordPress install and directories inside it as the web root.
	 */
	public function test_is_inside_web_root_for_install_directories(): void {
		$this->assertTrue( TempDirectoryGuard::is_inside_web_root( ABSPATH ), 'ABSPATH should be inside the web root' );
		$this->assertTrue( TempDirectoryGuard::is_inside_web_root( WP_CONTENT_DIR ), 'WP_CONTENT_DIR should be inside the web root' );
		$this->assertTrue( TempDirectoryGuard::is_inside_web_root( WP_CONTENT_DIR . '/not-created-yet' ), 'A missing directory under WP_CONTENT_DIR should be inside the web root' );
	}

	/**
	 * @testdox Should not match a directory that only shares a name prefix with the install.
	 */
	public function test_is_inside_web_root_ignores_a_sibling_with_the_same_prefix(): void {
		$this->assertFalse( TempDirectoryGuard::is_inside_web_root( untrailingslashit( ABSPATH ) . '-sibling' ) );
	}

	/**
	 * @testdox Should follow a symlink that points into the web root.
	 */
	public function test_is_inside_web_root_follows_symlinks(): void {
		$link = sys_get_temp_dir() . '/wc-temp-guard-link-' . wp_generate_password( 8, false );
		symlink( WP_CONTENT_DIR, $link );

		$result = TempDirectoryGuard::is_inside_web_root( $link );

		unlink( $link ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- removing a symlink created by this test.

		$this->assertTrue( $result, 'A symlink to WP_CONTENT_DIR should be inside the web root' );
	}

	/**
	 * @testdox Should accept the system temp directory.
	 */
	public function test_is_inside_web_root_is_false_for_the_system_temp_directory(): void {
		$this->assertFalse( TempDirectoryGuard::is_inside_web_root( sys_get_temp_dir() ) );
	}
}
