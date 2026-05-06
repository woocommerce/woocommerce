<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WC_Unit_Test_Case;
use WP_Filesystem_Base;
use WP_Filesystem_Direct;

/**
 * FilesystemUtilTest class.
 */
class FilesystemUtilTest extends WC_Unit_Test_Case {
	/**
	 * Tracked temp files to clean up after each test.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Tracked temp directories to clean up after each test.
	 *
	 * @var string[]
	 */
	private $temp_dirs = array();

	/**
	 * Set up before running any tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		}
		unset( $GLOBALS['wp_filesystem'] );
	}

	/**
	 * Tear down between each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->temp_files as $temp_file ) {
			if ( file_exists( $temp_file ) ) {
				unlink( $temp_file );
			}
		}
		foreach ( $this->temp_dirs as $temp_dir ) {
			if ( is_dir( $temp_dir ) ) {
				rmdir( $temp_dir );
			}
		}
		$this->temp_files = array();
		$this->temp_dirs  = array();

		unset( $GLOBALS['wp_filesystem'] );
		$this->reset_legacy_proxy_mocks();
		Constants::clear_constants();

		parent::tearDown();
	}

	/**
	 * Create a real temp file inside a directory and track it for cleanup.
	 *
	 * @param string $dir Directory to create the file in.
	 * @return string The absolute path of the file.
	 */
	private function make_temp_file( string $dir ): string {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			$this->temp_dirs[] = $dir;
		}
		$path               = tempnam( $dir, 'fsutil_' );
		$this->temp_files[] = $path;
		return $path;
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
	 * @testdox Check that get_wp_filesystem validates FTP filesystem instances.
	 *
	 * @testWith [true, true, true]
	 *           [false, false, true]
	 *           [false, true, false]
	 *
	 * @param bool $has_errors   Whether the mock should have connection errors.
	 * @param bool $has_link     Whether the mock should have a connection link.
	 * @param bool $should_throw Whether get_wp_filesystem should throw.
	 */
	public function test_get_wp_filesystem_validates_ftp( bool $has_errors, bool $has_link, bool $should_throw ): void {
		global $wp_filesystem;

		$mock_wp_filesystem         = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method = 'ftpext';
		$mock_wp_filesystem->errors = $has_errors ? new \WP_Error( 'connect', 'Failed to connect to FTP Server' ) : new \WP_Error();
		$mock_wp_filesystem->link   = $has_link ? true : null;
		$wp_filesystem              = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( $should_throw ) {
			$this->expectException( 'Exception' );
		}

		$result = FilesystemUtil::get_wp_filesystem();

		if ( ! $should_throw ) {
			$this->assertSame( $mock_wp_filesystem, $result );
		}
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

	/**
	 * @testdox 'get_wp_filesystem_direct' returns a WP_Filesystem_Direct instance regardless of FS_METHOD.
	 */
	public function test_get_wp_filesystem_direct_returns_direct_even_with_ftp_method(): void {
		Constants::set_constant( 'FS_METHOD', 'ftpext' );
		Constants::set_constant( 'FTP_HOST', 'ftp.example.com' );

		$result = FilesystemUtil::get_wp_filesystem_direct();

		$this->assertInstanceOf( WP_Filesystem_Direct::class, $result );
		$this->assertSame( 'direct', $result->method );
	}

	/**
	 * @testdox 'get_wp_filesystem_direct' returns the same cached instance on repeated calls.
	 */
	public function test_get_wp_filesystem_direct_caches_instance(): void {
		$first  = FilesystemUtil::get_wp_filesystem_direct();
		$second = FilesystemUtil::get_wp_filesystem_direct();

		$this->assertSame( $first, $second );
	}

	/**
	 * @testdox 'validate_upload_file_path' returns without throwing for a real file inside ABSPATH.
	 */
	public function test_validate_upload_file_path_success(): void {
		$this->expectNotToPerformAssertions();

		$path = $this->make_temp_file( ABSPATH );

		FilesystemUtil::validate_upload_file_path( $path );
	}

	/**
	 * @testdox 'validate_upload_file_path' throws if the file does not exist.
	 */
	public function test_validate_upload_file_path_failure_on_not_readable(): void {
		$this->expectException( 'Exception' );

		FilesystemUtil::validate_upload_file_path( ABSPATH . 'definitely-does-not-exist-' . wp_generate_uuid4() . '.txt' );
	}

	/**
	 * @testdox 'validate_upload_file_path' throws when the file is outside ABSPATH and the uploads directory.
	 */
	public function test_validate_upload_file_path_failure_on_not_in_directory(): void {
		$this->expectException( 'Exception' );

		$outside_dir = sys_get_temp_dir() . '/wc-fsutil-outside-' . wp_generate_uuid4();
		$path        = $this->make_temp_file( $outside_dir );

		// Make sure the temp file is genuinely outside ABSPATH and uploads.
		$abspath_real = wp_normalize_path( realpath( ABSPATH ) );
		$path_real    = wp_normalize_path( realpath( $path ) );
		$this->assertStringStartsNotWith( $abspath_real, $path_real );

		FilesystemUtil::validate_upload_file_path( $path );
	}

	/**
	 * @testdox 'validate_upload_file_path' returns without throwing for a real file inside the uploads directory.
	 */
	public function test_validate_upload_file_path_success_with_upload_dir(): void {
		$this->expectNotToPerformAssertions();

		$upload_dir = wp_get_upload_dir();
		$path       = $this->make_temp_file( $upload_dir['basedir'] );

		FilesystemUtil::validate_upload_file_path( $path );
	}

	/**
	 * @testdox 'validate_upload_file_path' accepts a file:// protocol prefix on a real path inside ABSPATH.
	 */
	public function test_validate_upload_file_path_success_with_file_protocol(): void {
		$this->expectNotToPerformAssertions();

		$path = $this->make_temp_file( ABSPATH );

		FilesystemUtil::validate_upload_file_path( 'file://' . $path );
	}
}
