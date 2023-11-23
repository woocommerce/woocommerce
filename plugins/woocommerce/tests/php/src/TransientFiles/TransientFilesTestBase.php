<?php

namespace Automattic\WooCommerce\Tests\TransientFiles;

use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Base class for the transient files engine related test classes.
 */
abstract class TransientFilesTestBase extends \WC_REST_Unit_Test_Case {
	/**
	 * The parent of the base directory for the transient files generated in the tests.
	 *
	 * @var string
	 */
	protected static string $base_transient_files_dir;

	/**
	 * The base directory for the transient files generated in the tests.
	 *
	 * @var string
	 */
	protected static string $transient_files_dir;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		global $wpdb;

		parent::setUp();
		$this->reset_container_resolutions();

		// Change the default templates directory to be the one in the tests directory.
		$this->register_legacy_proxy_function_mocks(
			array(
				'dirname' => function( $path ) {
					return false === StringUtil::ends_with( $path, '/TransientFiles/TransientFilesEngine.php' ) ?
						dirname( $path ) : __DIR__;
				},
			)
		);

		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_transient_files" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_transient_files_meta" );
		self::rmdir_recursive( self::$transient_files_dir, false );
	}

	/**
	 * Runs before all the tests in the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$base_transient_files_dir = sys_get_temp_dir() . '/wp-uploads';
		self::$transient_files_dir      = self::$base_transient_files_dir . '/woocommerce_transient_files';
		if ( ! is_dir( self::$transient_files_dir ) ) {
			wp_mkdir_p( self::$transient_files_dir );
		}
	}

	/**
	 * Runs after all the tests in the class.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::rmdir_recursive( self::$transient_files_dir, true );
	}

	/**
	 * Deletes a directory and all its contents.
	 *
	 * @param string $dirname The directory to delete.
	 * @param bool   $delete_root_dir True to delete $dirname too, false to delete only the contents of $dirname.
	 */
	protected static function rmdir_recursive( $dirname, bool $delete_root_dir ) {
		if ( ! is_dir( $dirname ) ) {
			return;
		}

		$dir = opendir( $dirname );
		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( '.' !== $file ) && ( '..' !== $file ) ) {
				$full = $dirname . '/' . $file;
				if ( is_dir( $full ) ) {
					self::rmdir_recursive( $full, $delete_root_dir );
				} else {
					unlink( $full );
				}
			}
		}
		closedir( $dir );
		if ( $delete_root_dir ) {
			rmdir( $dirname );
		}
	}
}
