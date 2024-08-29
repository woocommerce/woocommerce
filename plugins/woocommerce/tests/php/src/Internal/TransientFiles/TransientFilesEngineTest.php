<?php

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;

/**
 * Tests for the TransientFilesEngine class.
 */
class TransientFilesEngineTest extends \WC_REST_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var TransientFilesEngine
	 */
	private TransientFilesEngine $sut;

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
		parent::setUp();
		$this->reset_container_resolutions();

		self::rmdir_recursive( self::$transient_files_dir, false );
		$this->sut = $this->get_instance_of( TransientFilesEngine::class );
		$this->sut->register();
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
	 * @testdox create_transient_file throws an exception if no expiration date is supplied.
	 */
	public function test_create_transient_file_throws_if_invalid_expiration_date_is_supplied() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'BAD_DATE is not a valid date, expected format: YYYY-MM-DD' );
		$this->sut->create_transient_file( 'foobar', 'BAD_DATE' );
	}

	/**
	 * @testdox create_transient_file throws an exception if no expiration date is in the past.
	 */
	public function test_create_transient_file_throws_if_invalid_expiration_date_is_in_the_past() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'gmdate' => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The supplied expiration date, 2023-11-30, is in the past' );
		$this->sut->create_transient_file( 'foobar', '2023-11-30' );
	}

	/**
	 * @testdox create_transient_file throws an exception if the directory for the transient file can't be created.
	 */
	public function test_create_transient_file_throws_if_directory_cant_be_created() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => '/real' . $path,
				'is_dir'        => fn() => false,
				'wp_mkdir_p'    => fn() => false,
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			),
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Can't create directory: /real/wordpress/uploads/woocommerce_transient_files" );

		$this->sut->create_transient_file( 'foobar', '2023-12-02' );
	}

	/**
	 * @testdox create_transient_file throws an exception if the transient file can't be created.
	 */
	public function test_create_transient_file_throws_if_file_cant_be_created() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => '/real' . $path,
				'is_dir'        => fn() => true,
				'random_bytes'  => fn( $length ) => implode( array_map( 'chr', array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 ) ) ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			),
		);

		// phpcs:disable Squiz.Commenting.FunctionComment.Missing
		$fake_wp_filesystem = new class() {
			public function put_contents( string $file, string $contents, $mode = false ): bool {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting.FunctionComment.Missing

		$this->register_legacy_proxy_global_mocks(
			array(
				'wp_filesystem' => $fake_wp_filesystem,
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Can't create file: /real/wordpress/uploads/woocommerce_transient_files/2023-12-02/000102030405060708090a0b0c0d0e0f" );

		$this->sut->create_transient_file( 'foobar', '2023-12-02' );
	}

	/**
	 * @testdox create_transient_file creates the file if the input is correct. The current date is allowed as the expiration date.
	 *
	 * @testWith ["2023-12-01"]
	 *           ["2023-12-02"]
	 *
	 * @param string $expiration_date The expiration date for the created transient file.
	 */
	public function test_create_transient_file_works_as_expected_if_input_is_correct( string $expiration_date ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'random_bytes'  => fn( $count ) =>  implode( array_map( 'chr', array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 ) ) ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		$expected_file_name = '000102030405060708090a0b0c0d0e0f';
		$expected_file_path = static::$transient_files_dir . '/' . $expiration_date . '/' . $expected_file_name;

		$result = $this->sut->create_transient_file( 'foobar', $expiration_date );

		$this->assertEquals( '7e7c0' . substr( $expiration_date, strlen( $expiration_date ) - 1 ) . $expected_file_name, $result );
		$this->assertTrue( is_file( $expected_file_path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_get_contents( $expected_file_path );
		$this->assertEquals( 'foobar', $file_content );
	}

	/**
	 * @testdox The default base directory for the transient files is woocommerce_transient_files inside the WordPress uploads directory.
	 */
	public function test_transient_files_directory_is_rooted_in_uploads_directory() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => '/real' . $path,
			)
		);

		$result = $this->sut->get_transient_files_directory();

		$this->assertEquals( '/real/wordpress/uploads/woocommerce_transient_files', $result );
	}

	/**
	 * @testdox The base directory for the transient files can be modified with the woocommerce_transient_files_directory hook.
	 */
	public function test_transient_files_directory_can_be_changed_via_hook() {
		$original_directory = null;

		add_filter(
			'woocommerce_transient_files_directory',
			function( $path ) use ( &$original_directory ) {
				$original_directory = $path;
				return '/my/files';
			}
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => '/real' . $path,
			)
		);

		$result = $this->sut->get_transient_files_directory();

		remove_all_filters( 'woocommerce_transient_files_directory' );

		$this->assertEquals( '/wordpress/uploads/woocommerce_transient_files', $original_directory );
		$this->assertEquals( '/real/my/files', $result );
	}

	/**
	 * @testdox get_transient_files_directory throws if the calculated directory doesn't exist.
	 */
	public function test_get_transient_files_directory_throws_if_filter_is_used_and_directory_does_not_exist() {
		add_filter( 'woocommerce_transient_files_directory', fn( $default_dir) => 'foobar_dir' );

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => false,
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "The base transient files directory doesn't exist: foobar_dir" );

		try {
			$this->sut->get_transient_files_directory();
		} finally {
			remove_all_filters( 'woocommerce_transient_files_directory' );
		}
	}

	/**
	 * @testdox get_transient_files_directory creates the default base directory if it doesn't exist and the woocommerce_transient_files_directory filter is not used.
	 */
	public function test_get_transient_files_directory_creates_default_directory_if_it_does_not_exist() {
		$created_dir = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path ) => false,
				'wp_mkdir_p'    => function( $directory ) use ( &$created_dir ) {
					$created_dir = $directory;
					return true; },
			)
		);

		// phpcs:disable Squiz.Commenting
		$fake_wp_filesystem = new class() {
			public $created_files = array();

			public function put_contents( string $file, string $contents, $mode = false ): bool {
				$this->created_files[ $file ] = $contents;
				return strlen( $contents );
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_global_mocks(
			array(
				'wp_filesystem' => $fake_wp_filesystem,
			)
		);

		$this->sut->get_transient_files_directory();
		$this->assertEquals( '/wordpress/uploads/woocommerce_transient_files', $created_dir );

		$expected_created_files = array(
			'/wordpress/uploads/woocommerce_transient_files/.htaccess' => 'deny from all',
			'/wordpress/uploads/woocommerce_transient_files/index.html' => '',
		);
		$this->assertEquals( $expected_created_files, $fake_wp_filesystem->created_files );
	}

	/**
	 * @testdox get_transient_file_path returns null for a file that doesn't exist, including wrongly formatted names.
	 *
	 * @testWith [""]
	 *           ["123"]
	 *           ["NOT_HEX_DATE112233"]
	 *           ["000102030405060708090a0b0c0d0e0f"]
	 *
	 * @param string $filename The file name to test.
	 */
	public function test_get_transient_file_path_returns_null_for_non_existing_file( string $filename ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
			)
		);

		$this->assertNull( $this->sut->get_transient_file_path( $filename ) );
	}

	/**
	 * @testdox get_transient_file_path returns the physical path of an existing transient file.
	 */
	public function test_get_transient_file_path_works_as_expected_for_existing_file() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'random_bytes'  => fn( $count ) =>  implode( array_map( 'chr', array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 ) ) ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		$filename = $this->sut->create_transient_file( 'foobar', '2023-12-02' );

		$result = $this->sut->get_transient_file_path( $filename );
		$this->assertEquals( static::$transient_files_dir . '/2023-12-02/000102030405060708090a0b0c0d0e0f', $result );
	}

	/**
	 * @testdox get_expiration_date returns null for file names without a properly encoded expiration date.
	 *
	 * @testWith [""]
	 *           ["123"]
	 *           ["NOT_HEX_DATE112233"]
	 *           ["7e8f01"]
	 *
	 * @param string $filename Filename to test.
	 */
	public function test_get_expiration_date_returns_null_for_wrongly_formatted_date( string $filename ) {
		$this->assertNull( TransientFilesEngine::get_expiration_date( $filename ) );
	}

	/**
	 * @testdox get_expiration_date returns the date encoded in a proper transient file name.
	 */
	public function test_get_expiration_date_correctly_extracts_date_from_filename() {
		$actual = TransientFilesEngine::get_expiration_date( '7e821b00000' );
		$this->assertEquals( '2024-02-27', $actual );
	}

	/**
	 * @testdox get_public_url returns the full public URL of a transient file given its name.
	 */
	public function test_get_public_url() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_site_url' => fn( $blog_id, $path) => 'http://example.com' . $path,
			)
		);

		$actual = $this->sut->get_public_url( '1234abcd' );
		$this->assertEquals( 'http://example.com/wc/file/transient/1234abcd', $actual );
	}

	/**
	 * @testdox file_has_expired return false for a file that hasn't expired.
	 *
	 * @testWith ["2023-12-01"]
	 *           ["2023-12-02"]
	 *
	 * @param string $expiration_date The expiration date for the created transient file.
	 */
	public function test_file_has_expired_returns_false_if_file_has_not_expired( string $expiration_date ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		$filename  = $this->sut->create_transient_file( 'foobar', $expiration_date );
		$file_path = $this->sut->get_transient_file_path( $filename );

		$this->assertFalse( $this->sut->file_has_expired( $file_path ) );
	}

	/**
	 * @testdox file_has_expired return true for a file that hasn expired.
	 *
	 * @testWith ["2023-12-01"]
	 *           ["2023-12-02"]
	 *
	 * @param string $expiration_date The expiration date for the created transient file.
	 */
	public function test_file_has_expired_returns_true_if_file_has_expired( string $expiration_date ) {
		$today = '2023-12-01';

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => function( $format, $date = null ) use ( &$today ) {
					return is_null( $date ) && 'Y-m-d' === $format ? $today : gmdate( $format, $date );
				},
			)
		);

		$filename  = $this->sut->create_transient_file( 'foobar', $expiration_date );
		$file_path = $this->sut->get_transient_file_path( $filename );

		$today = '2023-12-03';
		$this->assertTrue( $this->sut->file_has_expired( $file_path ) );
	}

	/**
	 * @testdox delete_file deletes the file with the specified name and return true if the file exists.
	 */
	public function test_delete_file_deletes_existing_file_and_returns_true() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		$filename  = $this->sut->create_transient_file( 'foobar', '2023-12-02' );
		$file_path = $this->sut->get_transient_file_path( $filename );
		$this->assertTrue( is_file( $file_path ) );

		$result = $this->sut->delete_transient_file( $filename );

		$this->assertTrue( $result );
		$this->assertFalse( is_file( $file_path ) );
	}

	/**
	 * @testdox delete_file returns false if no file exists with the specified name.
	 */
	public function test_delete_file_returns_false_for_non_existing_file() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
			)
		);

		$result = $this->sut->delete_transient_file( 'NOT_EXISTING' );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox delete_expired_files deletes as many expired files as specified when calling it, and deletes empty date directories.
	 */
	public function test_delete_expired_files_does_what_it_says() {
		$today = '2023-12-01';

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => function( $format, $date = null ) use ( &$today ) {
					return is_null( $date ) && 'Y-m-d' === $format ? $today : gmdate( $format, $date );
				},
			)
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$this->sut->create_transient_file( 'foobar', sprintf( '2024-01-%02d', $i ) );
		}

		$today = '2024-02-01';

		// Not all the expired files are deleted.

		$result = $this->sut->delete_expired_files( 20 );

		$this->assertEquals(
			array(
				'deleted_count' => 20,
				'files_remain'  => true,
			),
			$result
		);

		for ( $i = 1; $i <= 20; $i++ ) {
			$this->assertFalse( is_dir( static::$transient_files_dir . sprintf( '/2024-01-%02d', $i ) ) );
		}
		for ( $i = 21; $i <= 30; $i++ ) {
			$this->assertTrue( is_dir( static::$transient_files_dir . sprintf( '/2024-01-%02d', $i ) ) );
		}

		// All the remaining expired files are deleted.

		$result = $this->sut->delete_expired_files( 15 );

		$this->assertEquals(
			array(
				'deleted_count' => 10,
				'files_remain'  => false,
			),
			$result
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$this->assertFalse( is_dir( static::$transient_files_dir . sprintf( '/2024-01-%02d', $i ) ) );
		}
	}

	/**
	 * @testdox delete_expired_files doesn't delete files that haven't expired.
	 */
	public function test_delete_expired_files_does_not_delete_not_expired_files() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
			)
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$this->sut->create_transient_file( 'foobar', sprintf( '2024-01-%02d', $i ) );
		}

		$result = $this->sut->delete_expired_files( 20 );

		$this->assertEquals(
			array(
				'deleted_count' => 0,
				'files_remain'  => false,
			),
			$result
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$this->assertTrue( is_dir( static::$transient_files_dir . sprintf( '/2024-01-%02d', $i ) ) );
		}
	}

	/**
	 * @testdox The expired files cleanup scheduled action reschedules itself for one day later if no files are deleted whatsoever.
	 */
	public function test_cleanup_scheduled_action_reschedules_for_one_day_later_if_no_files_are_deleted() {
		$actual_next_time = null;

		$this->recreate_sut();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir'             => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'time'                      => fn() => 10000000,
				'as_schedule_single_action' => function( $timestamp, $hook, $args, $group ) use ( &$actual_next_time ) {
					$actual_next_time = $timestamp;
				},
			)
		);

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'woocommerce_expired_transient_files_cleanup' );
		} finally {
			remove_all_filters( 'woocommerce_expired_transient_files_cleanup' );
		}

		$this->assertEquals( 10000000 + DAY_IN_SECONDS, $actual_next_time );
	}

	/**
	 * @testdox The expired files cleanup scheduled action reschedules itself to run immediately if at least one file is deleted.
	 */
	public function test_cleanup_scheduled_action_interval_can_be_modified_via_hook() {
		$actual_next_time = null;

		$this->recreate_sut();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir'             => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'time'                      => fn() => 10000000,
				'as_schedule_single_action' => function( $timestamp, $hook, $args, $group ) use ( &$actual_next_time ) {
					$actual_next_time = $timestamp;
				},
			)
		);

		add_filter( 'woocommerce_delete_expired_transient_files_interval', fn( $interval ) => HOUR_IN_SECONDS );

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'woocommerce_expired_transient_files_cleanup' );
		} finally {
			remove_all_filters( 'woocommerce_expired_transient_files_cleanup' );
			remove_all_filters( 'woocommerce_delete_expired_transient_files_interval' );
		}

		$this->assertEquals( 10000000 + HOUR_IN_SECONDS, $actual_next_time );
	}

	/**
	 * @testdox The interval for the next expired files cleanup scheduled action can be modified via the woocommerce_expired_transient_files_cleanup hook.
	 */
	public function test_cleanup_scheduled_action_reschedules_immediately_if_files_are_deleted() {
		$today            = '2023-12-01';
		$actual_next_time = null;

		$this->recreate_sut();

		$this->register_legacy_proxy_function_mocks(
			array(
				'dirname'                   =>
					function( $path ) {
						return false === StringUtil::ends_with( $path, '/TransientFiles/TransientFilesEngine.php' ) ?
							dirname( $path ) : __DIR__;
					},
				'wp_upload_dir'             => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'time'                      => fn() => 10000000,
				'gmdate'                    => function( $format, $date = null ) use ( &$today ) {
					return is_null( $date ) && 'Y-m-d' === $format ? $today : gmdate( $format, $date );
				},
				'as_schedule_single_action' =>
					function( $timestamp, $hook, $args, $group ) use ( &$actual_next_time ) {
						$actual_next_time = $timestamp;
					},
			)
		);

		$this->sut->create_transient_file( 'simple', '2023-12-02' );

		$today = '2023-12-03';

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'woocommerce_expired_transient_files_cleanup' );
		} finally {
			remove_all_filters( 'woocommerce_expired_transient_files_cleanup' );
		}

		$this->assertEquals( 10000001, $actual_next_time );
	}

	/**
	 * Recreate the tested instance of TransientFilesEngine.
	 *
	 * This is needed to unregister the hook handler that was registered by the first instance of TransientFilesEngine,
	 * created before the tests started and having a non-mockable LegacyProxy passed as dependency.
	 */
	private function recreate_sut() {
		remove_all_filters( 'woocommerce_expired_transient_files_cleanup' );
		$this->reset_container_resolutions();
		$this->sut = $this->get_instance_of( TransientFilesEngine::class );
		$this->sut->register();
	}

	/**
	 * @testdox The unauthenticated endpoint returns the contents of a non-expired existing file given its file name.
	 *
	 * Testing this endpoint is a bit contrived: we can't use "do_rest_get_request" because the endpoint is
	 * not registered as a JSON endpoint. Instead, we need to manually set the request global variables
	 * and then trigger the "parse_request" action.
	 *
	 * Additionally, since the endpoint is sending raw output with "echo" and terminating with "exit", we need to:
	 *
	 * 1. Capture the "echo"s with "ob_start" and friends.
	 *
	 * 2. Mock the "exit" so that it throws an exception that aborts the request but not the PHP execution
	 *    (otherwise "exit" prematurely ends the test). Therefore...
	 *
	 * 3. Capture the headers as they are set, since the exception will prevent them from being actually sent.
	 *
	 * capture the headers as they are sent, capture the output with "ob_*" methods,
	 * and mock "exit" so that it throws an exception.
	 */
	public function test_endpoint_returns_contents_for_existing_public_file(): void {
		$actual_headers = array();
		$actual_status  = null;

		remove_all_filters( 'parse_request' );
		$this->sut->register();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => fn( $format, $date = null ) =>
					is_null( $date ) && 'Y-m-d' === $format ? '2023-12-01' : gmdate( $format, $date ),
				'header'        => function( $header ) use ( &$actual_headers ) {
					$actual_headers[] = $header;
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					$actual_status = $status;
				},
			)
		);

		$this->register_exit_mock(
			function() {
				throw new \LogicException();
			}
		);

		$file_name = $this->sut->create_transient_file( 'foobar', '2024-01-01' );

		ob_start();
		$_GET['wc-transient-file-name'] = $file_name;
		$_SERVER['REQUEST_METHOD']      = 'GET';
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'parse_request' );
		} catch ( \LogicException $ex ) {
			$response_body = ob_get_clean();
		}

		$this->assertEquals( 200, $actual_status );
		$this->assertEquals( 'foobar', $response_body );

		$expected_headers = array(
			'Content-Type: text/html',
			'Content-Length: 6',
		);
		$this->assertEquals( $expected_headers, $actual_headers );
	}

	/**
	 * @testdox unauthenticated endpoint returns a 404 status for a file that doesn't exist.
	 */
	public function test_public_endpoint_returns_not_found_for_not_existing_file(): void {
		$actual_status = null;

		remove_all_filters( 'parse_request' );
		$this->sut->register();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'header'        => function( $header ) {
					// We aren't interested in headers, but we still need to mock the function
					// to prevent "headers already sent" errors being thrown in the PHPUnit context.
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					// The 500 is caused by the LogicException that is thrown by "exit"
					// after setting the real status code, thus we need to ignore it
					// and keep the real code that had been set previously.
					if ( 500 !== $status ) {
						$actual_status = $status;
					}
				},
			)
		);

		$this->register_exit_mock(
			function() {
				throw new \LogicException();
			}
		);

		ob_start();
		$_GET['wc-transient-file-name'] = 'NOT_EXISTING';
		$_SERVER['REQUEST_METHOD']      = 'GET';
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'parse_request' );
		} catch ( \LogicException $ex ) {
			$response_body = ob_get_clean();
		}

		$this->assertEquals( 404, $actual_status );
		$this->assertEquals( '', $response_body );
	}

	/**
	 * @testdox The unauthenticated endpoint returns a 404 status for a file that exists but has expired.
	 */
	public function test_endpoint_returns_not_found_for_expired_file(): void {
		$today         = '2023-12-01';
		$actual_status = null;

		remove_all_filters( 'parse_request' );
		$this->sut->register();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => static::$base_transient_files_dir ),
				'gmdate'        => function( $format, $date = null ) use ( &$today ) {
					return is_null( $date ) && 'Y-m-d' === $format ? $today : gmdate( $format, $date );
				},
				'header'        => function( $header ) {
					// We aren't interested in headers, but we still need to mock the function
					// to prevent "headers already sent" errors being thrown in the PHPUnit context.
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					// The 500 is caused by the LogicException that is thrown by "exit"
					// after setting the real status code, thus we need to ignore it
					// and keep the real code that had been set previously.
					if ( 500 !== $status ) {
						$actual_status = $status;
					}
				},
			)
		);

		$file_name = $this->sut->create_transient_file( 'foobar', '2024-01-01' );

		$this->register_exit_mock(
			function() {
				throw new \LogicException();
			}
		);

		$today = '2024-01-02';

		ob_start();
		$_GET['wc-transient-file-name'] = $file_name;
		$_SERVER['REQUEST_METHOD']      = 'GET';
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'parse_request' );
		} catch ( \LogicException $ex ) {
			$response_body = ob_get_clean();
		}

		$this->assertEquals( 404, $actual_status );
		$this->assertEquals( '', $response_body );
	}

	/**
	 * Recursively delete the contents of a directory.
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
