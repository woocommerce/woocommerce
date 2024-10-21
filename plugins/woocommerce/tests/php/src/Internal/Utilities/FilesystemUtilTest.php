<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WC_Unit_Test_Case;
use WP_Filesystem_Base;

/**
 * FilesystemUtilTest class.
 */
class FilesystemUtilTest extends WC_Unit_Test_Case {
	/**
	 * Set up before running any tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		unset( $GLOBALS['wp_filesystem'] );
	}

	/**
	 * Tear down between each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wp_filesystem'] );
		$this->reset_legacy_proxy_mocks();
		Constants::clear_constants();

		parent::tearDown();
	}

	/**
	 * @testdox Check that the get_wp_filesystem method returns an appropriate class instance.
	 */
	public function test_get_wp_filesystem_success(): void {
		$callback = fn() => 'direct';
		add_filter( 'filesystem_method', $callback );

		$this->assertInstanceOf( WP_Filesystem_Base::class, FilesystemUtil::get_wp_filesystem() );

		remove_filter( 'filesystem_method', $callback );
	}

	/**
	 * @testdox Check that the get_wp_filesystem method throws an exception when the filesystem cannot be initialized.
	 */
	public function test_get_wp_filesystem_failure(): void {
		$this->expectException( 'Exception' );

		$callback = fn() => 'asdf';
		add_filter( 'filesystem_method', $callback );

		FilesystemUtil::get_wp_filesystem();

		remove_filter( 'filesystem_method', $callback );
	}

	/**
	 * @testdox 'get_wp_filesystem_method_or_direct' returns 'direct' if no FS_METHOD constant, not 'ftp_credentials' option and not FTP_HOST constant exist.
	 */
	public function test_get_wp_filesystem_method_with_no_fs_method_nor_ftp_constant() {
		Constants::set_constant( 'FS_METHOD', null );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'            => fn( $name, $default_value = false ) => 'ftp_credentials' === $name ? false : get_option( $name, $default_value ),
				'get_filesystem_method' => function () {
					throw new \Exception( 'Unexpected call to get_filesystem_method' ); },
			)
		);
		Constants::set_constant( 'FTP_HOST', null );

		$this->assertEquals( 'direct', FilesystemUtil::get_wp_filesystem_method_or_direct() );
	}

	/**
	 * @testdox 'get_wp_filesystem_method_or_direct' invokes 'get_filesystem_method' if the FS_METHOD constant, the 'ftp_credentials' option or the FTP_HOST constant exist.
	 *
	 * @testWith ["method", false, null]
	 *           [null, "credentials", null]
	 *           [null, false, "host"]
	 *
	 * @param string|null  $fs_method_constant_value The value of the FS_METHOD constant to test.
	 * @param string|false $ftp_credentials_option_value The value of the 'ftp_credentials' option to test.
	 * @param string|false $ftp_host_option_value The value of the FTP_HOST constant to test.
	 */
	public function test_get_wp_filesystem_method_with_fs_method_or_ftp_constant( $fs_method_constant_value, $ftp_credentials_option_value, $ftp_host_option_value ) {
		Constants::set_constant( 'FS_METHOD', $fs_method_constant_value );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'            => fn( $name, $default_value = false ) => 'ftp_credentials' === $name ? $ftp_credentials_option_value : get_option( $name, $default_value ),
				'get_filesystem_method' => fn() => 'method',
			)
		);
		Constants::set_constant( 'FTP_HOST', $ftp_host_option_value );

		$this->assertEquals( 'method', FilesystemUtil::get_wp_filesystem_method_or_direct() );
	}

	/**
	 * 'get_wp_filesystem_method_or_direct' returns 'direct' if the FS_METHOD constant, the 'ftp_credentials' option or the FTP_HOST constant exist, and 'get_filesystem_method' fails.
	 *
	 * @testWith ["method", false, null]
	 *           [null, "credentials", null]
	 *           [null, false, "host"]
	 *
	 * @param string|null  $fs_method_constant_value The value of the FS_METHOD constant to test.
	 * @param string|false $ftp_credentials_option_value The value of the 'ftp_credentials' option to test.
	 * @param string|false $ftp_host_option_value The value of the FTP_HOST constant to test.
	 */
	public function test_get_wp_filesystem_method_with_fs_method_or_ftp_constant_and_no_wp_filesystem( $fs_method_constant_value, $ftp_credentials_option_value, $ftp_host_option_value ) {
		Constants::set_constant( 'FS_METHOD', $fs_method_constant_value );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'            => fn( $name, $default_value = false ) => 'ftp_credentials' === $name ? $ftp_credentials_option_value : get_option( $name, $default_value ),
				'get_filesystem_method' => fn() => false,
			)
		);
		Constants::set_constant( 'FTP_HOST', $ftp_host_option_value );

		$this->assertEquals( 'direct', FilesystemUtil::get_wp_filesystem_method_or_direct() );
	}
}
