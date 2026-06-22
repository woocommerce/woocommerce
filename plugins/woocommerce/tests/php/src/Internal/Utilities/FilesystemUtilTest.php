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
	 * @testdox 'validate_upload_file_path' returns without throwing an exception if the file path is valid.
	 */
	public function test_validate_upload_file_path_success() {
		$this->expectNotToPerformAssertions();

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( true );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( ABSPATH );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( ABSPATH . 'test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox 'validate_upload_file_path' throws an exception if the filesystem cannot be initialized.
	 */
	public function test_validate_upload_file_path_failure_on_initialize_wp_filesystem() {
		Constants::set_constant( 'FS_METHOD', null );

		$this->expectException( 'Exception' );

		FilesystemUtil::validate_upload_file_path( ABSPATH . 'test.txt' );
	}

	/**
	 * @testdox 'validate_upload_file_path' throws an exception if the file path is not readable.
	 */
	public function test_validate_upload_file_path_failure_on_not_readable() {
		$this->expectException( 'Exception' );

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( false );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( ABSPATH );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( ABSPATH . 'test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox 'validate_upload_file_path' throws an exception if the file path is not in the upload directory.
	 */
	public function test_validate_upload_file_path_failure_on_not_in_directory() {
		$this->expectException( 'Exception' );

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( true );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( ABSPATH );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( '/etc/test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox 'validate_upload_file_path' returns without throwing an exception if the file path is in the upload directory.
	 */
	public function test_validate_upload_file_path_success_with_upload_dir() {
		$this->expectNotToPerformAssertions();

		$callback = fn() => array(
			'path'    => '/uploads/',
			'basedir' => '/uploads/',
			'error'   => false,
		);
		add_filter( 'upload_dir', $callback );

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( true );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( ABSPATH );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( '/uploads/test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		remove_filter( 'upload_dir', $callback );
	}

	/**
	 * @testdox 'validate_upload_file_path' returns without throwing an exception if the file path has a file:// protocol.
	 */
	public function test_validate_upload_file_path_success_with_file_protocol() {
		$this->expectNotToPerformAssertions();

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( true );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( ABSPATH );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( 'file://' . ABSPATH . 'test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox 'validate_upload_file_path' returns without throwing an exception if the file path has a protocol other than file://.
	 */
	public function test_validate_upload_file_path_success_with_other_protocol() {
		$this->expectNotToPerformAssertions();

		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		$mock_wp_filesystem     = $this->createMock( WP_Filesystem_Base::class );
		$mock_wp_filesystem->method( 'is_readable' )->willReturn( true );
		$mock_wp_filesystem->method( 'abspath' )->willReturn( 's3://mock-bucket/' );
		$wp_filesystem = $mock_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		FilesystemUtil::validate_upload_file_path( 's3://mock-bucket/test.txt' );

		$wp_filesystem = $original_wp_filesystem; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox get_content_directory_relative_path() derives the path from WP_CONTENT_DIR when it lives under ABSPATH.
	 * @dataProvider provider_content_dir_under_abspath
	 *
	 * @param string $abspath        The ABSPATH value to use.
	 * @param string $wp_content_dir The WP_CONTENT_DIR value to use.
	 * @param string $expected       The expected root-relative content path.
	 */
	public function test_get_content_directory_relative_path_under_abspath( string $abspath, string $wp_content_dir, string $expected ): void {
		Constants::set_constant( 'ABSPATH', $abspath );
		Constants::set_constant( 'WP_CONTENT_DIR', $wp_content_dir );

		$this->assertSame( $expected, FilesystemUtil::get_content_directory_relative_path() );
	}

	/**
	 * Data provider for content directories located under ABSPATH.
	 *
	 * @return array<array<string>>
	 */
	public function provider_content_dir_under_abspath(): array {
		return array(
			// Default layout.
			array( '/var/www/html/', '/var/www/html/wp-content', '/wp-content' ),
			// Renamed content directory.
			array( '/var/www/html/', '/var/www/html/custom-content', '/custom-content' ),
			// Nested content directory.
			array( '/var/www/html/', '/var/www/html/wp/content', '/wp/content' ),
			// WordPress in a subdirectory.
			array( '/var/www/html/wp/', '/var/www/html/wp/wp-content', '/wp-content' ),
		);
	}

	/**
	 * @testdox get_content_directory_relative_path() falls back to the content URL path when WP_CONTENT_DIR is not under ABSPATH.
	 * @dataProvider provider_content_dir_outside_abspath
	 *
	 * @param string $abspath        The ABSPATH value to use.
	 * @param string $wp_content_dir The WP_CONTENT_DIR value to use.
	 */
	public function test_get_content_directory_relative_path_falls_back_to_content_url( string $abspath, string $wp_content_dir ): void {
		Constants::set_constant( 'ABSPATH', $abspath );
		Constants::set_constant( 'WP_CONTENT_DIR', $wp_content_dir );

		// When the content directory is not under ABSPATH the path must come from the content URL,
		// never from a bogus ABSPATH-relative substring of WP_CONTENT_DIR.
		$expected = wp_parse_url( content_url(), PHP_URL_PATH );

		$this->assertSame( $expected, FilesystemUtil::get_content_directory_relative_path() );
	}

	/**
	 * Data provider for content directories that are not located under ABSPATH.
	 *
	 * @return array<array<string>>
	 */
	public function provider_content_dir_outside_abspath(): array {
		return array(
			// Bedrock-style sibling directory.
			array( '/var/www/html/wp/', '/var/www/html/app' ),
			// Sibling sharing a name prefix (must not false-match ABSPATH).
			array( '/var/www/html/', '/var/www/htmlx/wp-content' ),
			// Unrelated absolute path.
			array( '/var/www/html/', '/totally/different/app' ),
			// Empty ABSPATH.
			array( '', '/var/www/html/wp-content' ),
		);
	}

	/**
	 * @testdox 'mkdir_p_not_indexable' writes the expected .htaccess based on the allow_file_access flag.
	 *
	 * @testWith [false, "deny from all"]
	 *           [true, "Options -Indexes"]
	 *
	 * @param bool   $allow_file_access Whether file access should be allowed.
	 * @param string $expected_htaccess The expected .htaccess content.
	 */
	public function test_mkdir_p_not_indexable_writes_expected_htaccess( bool $allow_file_access, string $expected_htaccess ): void {
		$callback = fn() => 'direct';
		add_filter( 'filesystem_method', $callback );

		$dir = trailingslashit( get_temp_dir() ) . 'wc-mkdir-not-indexable-' . ( $allow_file_access ? 'allow' : 'deny' );
		$this->delete_test_dir( $dir );

		try {
			FilesystemUtil::mkdir_p_not_indexable( $dir, $allow_file_access );

			$wp_fs = FilesystemUtil::get_wp_filesystem();
			$this->assertDirectoryExists( $dir, 'The directory should be created.' );
			$this->assertSame(
				$expected_htaccess,
				trim( (string) $wp_fs->get_contents( trailingslashit( $dir ) . '.htaccess' ) ),
				'The .htaccess content should reflect the allow_file_access flag.'
			);
			$this->assertTrue(
				$wp_fs->exists( trailingslashit( $dir ) . 'index.html' ),
				'An empty index.html should be created to prevent directory listing.'
			);
		} finally {
			$this->delete_test_dir( $dir );
			remove_filter( 'filesystem_method', $callback );
		}
	}

	/**
	 * @testdox 'mkdir_p_not_indexable' defaults to denying all access when no flag is passed.
	 */
	public function test_mkdir_p_not_indexable_defaults_to_deny_all(): void {
		$callback = fn() => 'direct';
		add_filter( 'filesystem_method', $callback );

		$dir = trailingslashit( get_temp_dir() ) . 'wc-mkdir-not-indexable-default';
		$this->delete_test_dir( $dir );

		try {
			FilesystemUtil::mkdir_p_not_indexable( $dir );

			$wp_fs = FilesystemUtil::get_wp_filesystem();
			$this->assertSame(
				'deny from all',
				trim( (string) $wp_fs->get_contents( trailingslashit( $dir ) . '.htaccess' ) ),
				'Omitting the allow_file_access argument should keep the deny-all default.'
			);
		} finally {
			$this->delete_test_dir( $dir );
			remove_filter( 'filesystem_method', $callback );
		}
	}

	/**
	 * @testdox 'mkdir_p_not_indexable' leaves an existing directory's .htaccess untouched.
	 */
	public function test_mkdir_p_not_indexable_does_not_overwrite_existing_directory(): void {
		$callback = fn() => 'direct';
		add_filter( 'filesystem_method', $callback );

		$dir = trailingslashit( get_temp_dir() ) . 'wc-mkdir-not-indexable-existing';
		$this->delete_test_dir( $dir );

		try {
			// First call creates the directory with the deny-all default.
			FilesystemUtil::mkdir_p_not_indexable( $dir );

			// A later call requesting file access must not rewrite the existing .htaccess.
			FilesystemUtil::mkdir_p_not_indexable( $dir, true );

			$wp_fs = FilesystemUtil::get_wp_filesystem();
			$this->assertSame(
				'deny from all',
				trim( (string) $wp_fs->get_contents( trailingslashit( $dir ) . '.htaccess' ) ),
				'An existing directory should keep its original .htaccess.'
			);
		} finally {
			$this->delete_test_dir( $dir );
			remove_filter( 'filesystem_method', $callback );
		}
	}

	/**
	 * Removes a test directory and its contents if it exists.
	 *
	 * @param string $dir The directory to delete.
	 * @return void
	 */
	private function delete_test_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		FilesystemUtil::get_wp_filesystem()->rmdir( $dir, true );
	}
}
