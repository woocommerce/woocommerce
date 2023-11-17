<?php

namespace Automattic\WooCommerce\Tests\TransientFiles;

use Automattic\WooCommerce\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Tests for the TransientFilesEngine class.
 */
class TransientFilesEngineTest extends \WC_Unit_Test_Case {
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
	private static string $base_transient_files_dir;

	/**
	 * The base directory for the transient files generated in the tests.
	 *
	 * @var string
	 */
	private static string $transient_files_dir;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		global $wpdb;

		parent::set_up();
		$this->reset_container_resolutions();
		$this->sut = $this->get_instance_of( TransientFilesEngine::class );

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
	 * @testdox create_file_by_rendering_template throws an exception if the specified template is not found.
	 */
	public function test_create_file_by_rendering_template_throws_if_template_not_found() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template not found: BADTEMPLATE' );

		$this->sut->create_file_by_rendering_template( 'BADTEMPLATE', array() );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if directory traversal is attempted when specifying the template path.
	 */
	public function test_create_file_by_rendering_template_throws_if_directory_traversal_is_attempted() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Template not found: ../misplaced' );

		$this->sut->create_file_by_rendering_template( '../misplaced', array() );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if an infinite loop of template sub-rendering is detected.
	 */
	public function test_create_file_by_rendering_template_throws_on_infinite_loop() {
		$original_xdebug_max_nesting_level = ini_get( 'xdebug.max_nesting_level' );

		// phpcs:disable WordPress.PHP.IniSet.Risky

		// This is needed to prevent "Xdebug has detected a possible infinite loop" error to be thrown
		// before we detect the infinite loop ourselves.
		ini_set( 'xdebug.max_nesting_level', 10000 );

		$this->expectException( \OverflowException::class );
		$this->expectExceptionMessage( 'Template rendering depth of 256 levels reached, possible circular reference when rendering secondary templates.' );

		try {
			$this->sut->create_file_by_rendering_template( 'infinite_loop', array() );
		} finally {
			ini_set( 'xdebug.max_nesting_level', $original_xdebug_max_nesting_level );
		}

		// phpcs:enable WordPress.PHP.IniSet.Risky
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if no expiration date is supplied as part of the metadata.
	 */
	public function test_create_file_by_rendering_template_throws_if_no_expiration_date_is_supplied() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The metadata array must have either an expiration_date key or an expiration_seconds key' );

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'no_expiration_date_here' => 'nope' ) );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if an invalid expiration date is supplied as part of the metadata.
	 *
	 * @testWith ["BAD_DATE", "BAD_DATE"]
	 *           [[], "array"]
	 *
	 * @param mixed  $value The value of the expiration date to use.
	 * @param string $expected_value_representation The expected string representation of the $value in the error message.
	 */
	public function test_create_file_by_rendering_template_throws_if_invalid_expiration_date_is_supplied( $value, string $expected_value_representation ) {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "$expected_value_representation is not a valid date, expected format: year-month-day hour:minute:second" );

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_date' => $value ) );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if an invalid expiration seconds value is supplied as part of the metadata.
	 *
	 * @testWith ["BAD_SECONDS", "BAD_SECONDS"]
	 *           [[], "array"]
	 *           [59, "59"]
	 *           [-1, "-1"]
	 *
	 * @param mixed  $value The value of the expiration date to use.
	 * @param string $expected_value_representation The expected string representation of the $value in the error message.
	 */
	public function test_create_file_by_rendering_template_throws_if_invalid_expiration_seconds_is_supplied( $value, string $expected_value_representation ) {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "expiration_seconds must be a number and have a minimum value of 60, got $expected_value_representation" );

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_seconds' => $value ) );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if the supplied expiration date is smaller than the minimum allowed value.
	 */
	public function test_create_file_by_rendering_template_throws_if_expiration_date_is_too_soon() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'expiration_date must be higher than the current time plus 60 seconds, got 2023-11-01 12:34:56' );

		$this->register_legacy_proxy_function_mocks(
			array(
				'current_time' => fn( $type, $gmt) =>
						$gmt && 'timestamp' === $type ? strtotime( '2023-11-01 12:34:00' ) : current_time( $type, $gmt ),
			)
		);

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_date' => '2023-11-01 12:34:56' ) );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if the directory for the transient file can't be created.
	 */
	public function test_create_file_by_rendering_template_throws_if_directory_cant_be_created() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path) => '/real' . $path,
				'is_dir'        => fn() => false,
				'wp_mkdir_p'    => fn() => false,
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Can't create directory: /real/wordpress/uploads/woocommerce_transient_files" );

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_date' => '2100-01-01 00:00:00' ) );
	}

	/**
	 * @testdox create_file_by_rendering_template throws an exception if the transient file can't be created.
	 */
	public function test_create_file_by_rendering_template_throws_if_file_cant_be_created() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path) => '/real' . $path,
				'is_dir'        => fn() => true,
				'fopen'         => fn() => false,
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Can't create transient file, rendering template 'simple'" );

		$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_date' => '2100-01-01 00:00:00' ) );
	}

	/**
	 * @testdox create_file_by_rendering_template returns the file contents directly if no metadata array is supplied.
	 */
	public function test_create_file_by_rendering_template_returns_file_contents_as_string_if_no_metadata_supplied() {
		$result = $this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			)
		);
		$this->assertEquals( 'Simple: foo = TheFoo, bar = TheBar', trim( $result ) );
	}

	/**
	 * @testdox create_file_by_rendering_template supports explicit file extensions for the template file names.
	 */
	public function test_create_file_by_rendering_template_supports_explicit_extension() {
		$result = $this->sut->create_file_by_rendering_template( 'with_custom.extension', array() );
		$this->assertEquals( 'Template with custom extension.', trim( $result ) );
	}

	/**
	 * @testdox create_file_by_rendering_template supports rendering subtemplates from inside the template itself.
	 */
	public function test_create_file_by_rendering_template_with_subtemplates() {
		$result   = $this->sut->create_file_by_rendering_template(
			'complex/main',
			array(
				'foo' => 'foo 1',
				'bar' => 'TheBar',
			)
		);
		$expected =
		'Main template.
Initial foo = foo 1, bar = TheBar
Subtemplate! foo = foo 2, bar = TheBar
Subtemplate! foo = foo 3, bar = TheBar
Simple: foo = foo 4, bar = TheBar 2
Final foo = foo 1, bar = TheBar';
		$this->assertEquals( $expected, trim( $result ) );
	}

	/**
	 * @testdox create_file_by_rendering_template generates the transient file and creates the appropriate database entries, passing an expiration date.
	 *
	 * @testWith [true]
	 *           [false]
	 *           [null]
	 *
	 * @param bool $is_public Whether the file is rendered as public or not.
	 */
	public function test_create_file_by_rendering_template_to_file_with_expiration_date( ?bool $is_public ) {
		global $wpdb;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'random_bytes'  => fn( $count) =>  implode( array_map( 'chr', array( 0x00, 0x11, 0x22, 0x33, 0x44, 0x55, 0x66, 0x77, 0x88, 0x99, 0xAA, 0xBB, 0xCC, 0xDD, 0xEE, 0xFF ) ) ),
				'current_time'  => fn( $type, $gmt) =>
					$gmt && 'mysql' === $type ? '2023-11-22 01:23:34' : current_time( $type, $gmt ),
			)
		);

		$expected_file_name = '00112233445566778899aabbccddeeff';
		$expected_file_path = self::$transient_files_dir . '/2034-07-01/' . $expected_file_name;

		$metadata = array(
			'expiration_date' => '2034-07-01 12:34:56',
			'content-type'    => 'foo/bar',
		);
		if ( ! is_null( $is_public ) ) {
			$metadata['is_public'] = $is_public;
		}
		$result = $this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			$metadata
		);

		$this->assertEquals( $expected_file_name, $result );
		$this->assertTrue( is_file( $expected_file_path ) );

		$db_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_transient_files WHERE file_name=%s",
				$expected_file_name
			),
			ARRAY_A
		);

		$this->assertEquals( $expected_file_name, $db_row['file_name'] );
		$this->assertEquals( '2023-11-22 01:23:34', $db_row['date_created_gmt'] );
		$this->assertEquals( '2034-07-01 12:34:56', $db_row['expiration_date_gmt'] );
		$this->assertEquals( true === $is_public ? 1 : 0, $db_row['is_public'] );

		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_transient_files_meta WHERE transient_file_id=%d",
				$db_row['id']
			),
			ARRAY_A
		);

		$this->assertEquals( 1, count( $meta_rows ) );
		$meta_row = current( $meta_rows );
		$this->assertEquals( 'content-type', $meta_row['meta_key'] );
		$this->assertEquals( 'foo/bar', $meta_row['meta_value'] );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_content = file_get_contents( $expected_file_path );
		$this->assertEquals( 'Simple: foo = TheFoo, bar = TheBar', $file_content );

		return $db_row;
	}

	/**
	 * @testdox create_file_by_rendering_template generates the transient file and creates the appropriate database entries, passing an expiration seconds value.
	 */
	public function test_create_file_by_rendering_template_to_file_with_expiration_seconds() {
		global $wpdb;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'current_time'  => fn( $type, $gmt) =>
				$gmt && 'mysql' === $type ? '2023-11-22 01:23:34' : current_time( $type, $gmt ),
			)
		);

		$file_name = $this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			array( 'expiration_seconds' => HOUR_IN_SECONDS )
		);

		$db_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_transient_files WHERE file_name=%s",
				$file_name
			),
			ARRAY_A
		);

		$this->assertEquals( '2023-11-22 02:23:34', $db_row['expiration_date_gmt'] );
	}

	/**
	 * @testdox The metadata passed to create_file_by_rendering_template can be modified via the woocommerce_transient_file_creation_metadata hook.
	 */
	public function test_rendered_template_metadata_can_be_changed_via_hook() {
		global $wpdb;

		$actual_template_name      = null;
		$actual_template_variables = null;

		$this->register_legacy_proxy_function_mocks(
			array( 'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ) )
		);

		add_filter(
			'woocommerce_transient_file_creation_metadata',
			function( $metadata, $template_name, $variables ) use ( &$actual_template_name, &$actual_template_variables ) {
				$actual_template_name      = $template_name;
				$actual_template_variables = $variables;

				$metadata['foo'] = 'bar';
				return $metadata;
			},
			10,
			3
		);

		try {
			$file_name = $this->sut->create_file_by_rendering_template(
				'simple',
				array(
					'foo' => 'TheFoo',
					'bar' => 'TheBar',
				),
				array( 'expiration_seconds' => 60 )
			);
		} finally {
			remove_all_filters( 'woocommerce_transient_file_creation_metadata' );
		}

		$db_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_transient_files_meta WHERE transient_file_id in (select id from {$wpdb->prefix}wc_transient_files where file_name=%s)",
				$file_name
			),
			ARRAY_A
		);

		$this->assertEquals( 'simple', $actual_template_name );
		$this->assertEquals(
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			$actual_template_variables
		);

		$this->assertEquals( 'foo', $db_row['meta_key'] );
		$this->assertEquals( 'bar', $db_row['meta_value'] );
	}

	/**
	 * @testdox The name of the file created by create_file_by_rendering_template can be modified via the woocommerce_transient_file_creation_filename hook.
	 */
	public function test_rendered_template_filename_can_be_changed_via_hook() {
		$actual_template_name      = null;
		$actual_template_variables = null;
		$actual_template_metadata  = null;

		$this->register_legacy_proxy_function_mocks(
			array( 'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ) )
		);

		add_filter(
			'woocommerce_transient_file_creation_filename',
			function( $filename, $template_name, $variables, $metadata ) use ( &$actual_template_name, &$actual_template_variables, &$actual_template_metadata ) {
				$actual_template_name      = $template_name;
				$actual_template_variables = $variables;
				$actual_template_metadata  = $metadata;

				return 'FOOBAR_FILE';
			},
			10,
			4
		);

		try {
			$file_name = $this->sut->create_file_by_rendering_template(
				'simple',
				array(
					'foo' => 'TheFoo',
					'bar' => 'TheBar',
				),
				array(
					'expiration_date' => '2034-01-10 00:00:00',
					'fizz'            => 'buzz',
				)
			);
		} finally {
			remove_all_filters( 'woocommerce_transient_file_creation_filename' );
		}

		$this->assertEquals( 'FOOBAR_FILE', $file_name );
		$this->assertTrue( is_file( self::$transient_files_dir . '/2034-01-10/FOOBAR_FILE' ) );

		$this->assertEquals( 'simple', $actual_template_name );
		$this->assertEquals(
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			$actual_template_variables
		);
		$this->assertEquals( array( 'fizz' => 'buzz' ), $actual_template_metadata );
	}

	/**
	 * @testdox The minimum allowed expiration seconds for files created with create_file_by_rendering_template can be changed via the woocommerce_transient_files_minimum_expiration_seconds hook.
	 */
	public function test_minimum_expiration_seconds_can_be_changed_via_hook() {
		$this->register_legacy_proxy_function_mocks(
			array( 'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ) )
		);

		add_filter( 'woocommerce_transient_files_minimum_expiration_seconds', fn( $seconds) => 120 );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'expiration_seconds must be a number and have a minimum value of 120, got 100' );

		try {
			$this->sut->create_file_by_rendering_template( 'simple', array(), array( 'expiration_seconds' => 100 ) );
		} finally {
			remove_all_filters( 'woocommerce_transient_files_minimum_expiration_seconds' );
		}
	}

	/**
	 * @testdox The path of the template to be rendered with create_file_by_rendering_template can be changed via the woocommerce_transient_file_creation_template_file_path hook.
	 */
	public function test_template_file_path_can_be_changed_via_hook() {
		$actual_template_path        = null;
		$actual_template_name        = null;
		$actual_parent_template_path = null;

		$this->register_legacy_proxy_function_mocks(
			array( 'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ) )
		);

		add_filter(
			'woocommerce_transient_file_creation_template_file_path',
			function( $template_path, $template_name, $parent_template_path ) use ( &$actual_template_path, &$actual_template_name, &$actual_parent_template_path ) {
				$actual_template_path        = $template_path;
				$actual_template_name        = $template_name;
				$actual_parent_template_path = $parent_template_path;

				if ( 'misplaced' === $template_name ) {
					return __DIR__ . '/misplaced.template';
				}

				return $template_path;
			},
			10,
			3
		);

		try {
			$file_name = $this->sut->create_file_by_rendering_template( 'misplaced', array(), array( 'expiration_date' => '2034-01-10 00:00:00' ) );
		} finally {
			remove_all_filters( 'woocommerce_transient_file_creation_template_file_path' );
		}

		$this->assertNull( $actual_template_path ); // This means that the template doesn't exist at the default location.
		$this->assertEquals( 'misplaced', $actual_template_name );
		$this->assertNull( $actual_parent_template_path );

		$file_path = self::$transient_files_dir . '/2034-01-10/' . $file_name;
		$this->assertTrue( is_file( $file_path ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertEquals( 'This template shouldn\'t be reachable since it\'s outside of the base "Templates" directory.', trim( file_get_contents( $file_path ) ) );
	}

	/**
	 * @testdox The default base directory for the transient files is woocommerce_transient_files inside the WordPress uploads directory.
	 */
	public function test_transient_files_directory_is_rooted_in_uploads_directory() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path) => '/real' . $path,
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
				return '/my/templates';
			}
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path) => '/real' . $path,
			)
		);

		$result = $this->sut->get_transient_files_directory();

		remove_all_filters( 'woocommerce_transient_files_directory' );

		$this->assertEquals( '/wordpress/uploads/woocommerce_transient_files', $original_directory );
		$this->assertEquals( '/real/my/templates', $result );
	}

	/**
	 * @testdox get_transient_files_directory throws if the calculated directory doesn't exist.
	 */
	public function test_get_transient_files_directory_throws_if_directory_does_not_exist() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/wordpress/uploads' ),
				'realpath'      => fn( $path) => false,
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "The base transient files directory doesn't exist: /wordpress/uploads/woocommerce_transient_files" );

		$this->sut->get_transient_files_directory();
	}

	/**
	 * @testdox get_file_by_id returns null if no transient file with the passed id exists.
	 */
	public function test_get_file_by_id_returns_null_on_file_not_found() {
		$result = $this->sut->get_file_by_id( 99999 );
		$this->assertNull( $result );
	}

	/**
	 * @testdox get_file_by_id returns information about the transient file that has the supplied id.
	 *
	 * @testWith [true]
	 *           [false]
	 *           [null]
	 *
	 * @param bool $is_public Whether the file is created as public or not.
	 */
	public function test_get_file_by_id_returns_proper_file_information( ?bool $is_public ) {
		$db_row = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( $is_public );

		$result = $this->sut->get_file_by_id( $db_row['id'], true );

		unset( $result['id'] );
		$expected = array(
			'file_name'           => '00112233445566778899aabbccddeeff',
			'date_created_gmt'    => '2023-11-22 01:23:34',
			'expiration_date_gmt' => '2034-07-01 12:34:56',
			'is_public'           => true === $is_public,
			'file_path'           => self::$transient_files_dir . '/2034-07-01/00112233445566778899aabbccddeeff',
			'has_expired'         => false,
			'metadata'            => array( 'content-type' => 'foo/bar' ),
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox get_file_by_name returns null if no transient file with the passed file name exists.
	 */
	public function test_get_file_by_name_returns_null_on_file_not_found() {
		$result = $this->sut->get_file_by_name( 'BAD_NAME' );
		$this->assertNull( $result );
	}

	/**
	 * @testdox get_file_by_name returns information about the transient file that has the supplied file name.
	 *
	 * @testWith [true]
	 *           [false]
	 *           [null]
	 *
	 * @param bool $is_public Whether the file is created as public or not.
	 */
	public function test_get_file_by_name_returns_proper_file_information( ?bool $is_public ) {
		$db_row = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( $is_public );

		$result = $this->sut->get_file_by_name( $db_row['file_name'] );

		unset( $result['id'] );
		$expected = array(
			'file_name'           => '00112233445566778899aabbccddeeff',
			'date_created_gmt'    => '2023-11-22 01:23:34',
			'expiration_date_gmt' => '2034-07-01 12:34:56',
			'is_public'           => true === $is_public,
			'file_path'           => self::$transient_files_dir . '/2034-07-01/00112233445566778899aabbccddeeff',
			'has_expired'         => false,
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox get_file methods returns 'has_expired' as true if the file is past its expiration date.
	 */
	public function test_get_file_tells_if_it_has_expired() {
		global $wpdb;

		$db_row = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( false );

		$wpdb->query(
			$wpdb->prepare( "UPDATE {$wpdb->prefix}wc_transient_files SET expiration_date_gmt='2020-01-01 00:00:00' WHERE id=%d", $db_row['id'] )
		);

		$result = $this->sut->get_file_by_id( $db_row['id'] );
		$this->assertTrue( $result['has_expired'] );
	}

	/**
	 * @testdox get_file methods deletes the file if it's past its expiration date and the $delete_if_expired argument is passed as true.
	 */
	public function test_get_file_can_autodelete_expired_file() {
		global $wpdb;

		$db_row = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( false );

		$wpdb->query(
			$wpdb->prepare( "UPDATE {$wpdb->prefix}wc_transient_files SET expiration_date_gmt='2020-01-01 00:00:00' WHERE id=%d", $db_row['id'] )
		);

		$result = $this->sut->get_file_by_id( $db_row['id'], false, true );
		$this->assertNull( $result );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_transient_files WHERE id=%d",
				$db_row['id']
			)
		);
		$this->assertNull( $id );
	}

	/**
	 * @testdox delete_file_by_id returns false if no transient file exists with the supplied id.
	 */
	public function test_delete_file_by_id_returns_false_for_non_existing_files() {
		$result = $this->sut->delete_file_by_id( 99999 );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox delete_file_by_id deletes both the file and the database entries.
	 */
	public function test_delete_file_by_id_deletes_the_file() {
		global $wpdb;

		$db_row    = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( false );
		$file_info = $this->sut->get_file_by_id( $db_row['id'] );

		$result = $this->sut->delete_file_by_id( $db_row['id'] );

		$this->assertTrue( $result );
		$this->assertFalse( is_file( $file_info['file_path'] ) );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_transient_files WHERE id=%d",
				$db_row['id']
			)
		);
		$this->assertNull( $id );
	}

	/**
	 * @testdox delete_file_by_name returns false if no transient file exists with the supplied file name.
	 */
	public function test_delete_file_by_name_returns_false_for_non_existing_files() {
		$result = $this->sut->delete_file_by_name( 'INVALID_NAME' );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox delete_file_by_name deletes both the file and the database entries.
	 */
	public function test_delete_file_by_name_deletes_the_file() {
		global $wpdb;

		$db_row    = $this->test_create_file_by_rendering_template_to_file_with_expiration_date( false );
		$file_info = $this->sut->get_file_by_id( $db_row['id'] );

		$result = $this->sut->delete_file_by_name( $db_row['file_name'] );

		$this->assertTrue( $result );
		$this->assertFalse( is_file( $file_info['file_path'] ) );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_transient_files WHERE id=%d",
				$db_row['id']
			)
		);
		$this->assertNull( $id );
	}

	/**
	 * @testdox delete_expired_files deletes files, and maybe also databse entries, corresponding to files expired in the previous day or earlier.
	 */
	public function test_delete_expired_files() {
		global $wpdb;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'current_time'  => fn( $type, $gmt) =>
				$gmt && 'timestamp' === $type ? strtotime( '2020-01-01 00:00:00' ) : current_time( $type, $gmt ),
			)
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$metadata = array( 'expiration_date' => sprintf( '2022-10-%02d 00:00:00', $i ) );
			$this->sut->create_file_by_rendering_template(
				'simple',
				array(
					'foo' => 'TheFoo',
					'bar' => 'TheBar',
				),
				$metadata
			);
		}

		$this->register_legacy_proxy_function_mocks(
			array(
				'current_time' => fn( $type, $gmt) =>
								$gmt && 'mysql' === $type ? '2023-01-01 00:00:00' : current_time( $type, $gmt ),
			)
		);

		// Not all the expired files are deleted, then no rows are deleted at all.

		$result = $this->sut->delete_expired_files( 20 );

		$this->assertEquals(
			array(
				'files_deleted' => 20,
				'rows_deleted'  => 0,
			),
			$result
		);

		for ( $i = 1; $i <= 20; $i++ ) {
			$this->assertFalse( is_dir( self::$transient_files_dir . sprintf( '/2022-10-%02d', $i ) ) );
		}
		for ( $i = 21; $i <= 30; $i++ ) {
			$this->assertTrue( is_dir( self::$transient_files_dir . sprintf( '/2022-10-%02d', $i ) ) );
		}

		$dates_in_db          = $wpdb->get_results( "SELECT expiration_date_gmt FROM {$wpdb->prefix}wc_transient_files ORDER BY expiration_date_gmt", ARRAY_N );
		$dates_in_db          = array_map( fn( $item) => current( $item ), $dates_in_db );
		$expected_dates_in_db = array_map( fn( $num) => sprintf( '2022-10-%02d 00:00:00', $num ), range( 1, 30 ) );
		$this->assertEquals( $expected_dates_in_db, $dates_in_db );

		// All the expired files are deleted, then rows are deleted.

		$result = $this->sut->delete_expired_files( 15 );

		$this->assertEquals(
			array(
				'files_deleted' => 10,
				'rows_deleted'  => 15,
			),
			$result
		);

		for ( $i = 1; $i <= 30; $i++ ) {
			$this->assertFalse( is_dir( self::$transient_files_dir . sprintf( '/2022-10-%02d', $i ) ) );
		}

		$dates_in_db          = $wpdb->get_results( "SELECT expiration_date_gmt FROM {$wpdb->prefix}wc_transient_files ORDER BY expiration_date_gmt", ARRAY_N );
		$dates_in_db          = array_map( fn( $item) => current( $item ), $dates_in_db );
		$expected_dates_in_db = array_map( fn( $num) => sprintf( '2022-10-%02d 00:00:00', $num ), range( 16, 30 ) );
		$this->assertEquals( $expected_dates_in_db, $dates_in_db );

		// No files left, all remaining rows are deleted now.

		$result = $this->sut->delete_expired_files( 20 );

		$this->assertEquals(
			array(
				'files_deleted' => 0,
				'rows_deleted'  => 15,
			),
			$result
		);

		$dates_in_db = $wpdb->get_results( "SELECT expiration_date_gmt FROM {$wpdb->prefix}wc_transient_files ORDER BY expiration_date_gmt", ARRAY_N );
		$this->assertEmpty( $dates_in_db );

		// Nothing left to delete.

		$result = $this->sut->delete_expired_files( 20 );

		$this->assertEquals(
			array(
				'files_deleted' => 0,
				'rows_deleted'  => 0,
			),
			$result
		);
	}

	/**
	 * @testdox delete_expired_files does not delete files that aren't past their expiration dates, or that have expired in the current day.
	 */
	public function test_delete_expired_files_does_not_delete_files_expired_today_or_not_expired() {
		global $wpdb;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'current_time'  => fn( $type, $gmt) =>
				$gmt && 'timestamp' === $type ? strtotime( '2020-01-01 00:00:00' ) : current_time( $type, $gmt ),
			)
		);

		$this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			array( 'expiration_date' => '2024-01-01 00:00:00' )
		);
		$this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			array( 'expiration_date' => '2023-11-01 00:00:00' )
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'current_time' => fn( $type, $gmt) =>
							$gmt && 'mysql' === $type ? '2023-11-01 10:00:00' : current_time( $type, $gmt ),
			)
		);

		$result = $this->sut->delete_expired_files();
		$this->assertEquals(
			array(
				'files_deleted' => 0,
				'rows_deleted'  => 0,
			),
			$result
		);

		$result = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}wc_transient_files" );
		$this->assertEquals( 2, $result );

		$this->assertTrue( is_dir( self::$transient_files_dir . '/2024-01-01' ) );
		$this->assertTrue( is_dir( self::$transient_files_dir . '/2023-11-01' ) );
	}

	/**
	 * @testdox The expired files cleanup scheduled action reschedules itself for one day later if no files are deleted whatsoever.
	 */
	public function test_cleanup_scheduled_action_reschedules_for_one_day_later_if_no_files_are_deleted() {
		$actual_next_time = null;

		$this->recreate_sut();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir'             => fn() => array( 'basedir' => self::$base_transient_files_dir ),
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
				'wp_upload_dir'             => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'time'                      => fn() => 10000000,
				'as_schedule_single_action' => function( $timestamp, $hook, $args, $group ) use ( &$actual_next_time ) {
					$actual_next_time = $timestamp;
				},
			)
		);

		add_filter( 'woocommerce_delete_expired_transient_files_interval', fn( $interval) => HOUR_IN_SECONDS );

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
		$actual_next_time = null;

		$this->recreate_sut();

		$this->register_legacy_proxy_function_mocks(
			array(
				'dirname'                   =>
					function( $path ) {
						return false === StringUtil::ends_with( $path, '/TransientFiles/TransientFilesEngine.php' ) ?
						dirname( $path ) : __DIR__;
					},
				'wp_upload_dir'             => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'time'                      => fn() => 10000000,
				'current_time'              =>
					fn( $type, $gmt) =>
						$gmt && 'timestamp' === $type ? strtotime( '2020-01-01 00:00:00' ) : current_time( $type, $gmt ),
				'as_schedule_single_action' =>
					function( $timestamp, $hook, $args, $group ) use ( &$actual_next_time ) {
						$actual_next_time = $timestamp;
					},
			)
		);

		$this->sut->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			array( 'expiration_date' => '2022-01-01 00:00:00' )
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'current_time' => fn( $type, $gmt) =>
							$gmt && 'mysql' === $type ? '2023-01-01 00:00:00' : current_time( $type, $gmt ),
			)
		);

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
	}

	/**
	 * Deletes a directory and all its contents.
	 *
	 * @param string $dirname The directory to delete.
	 * @param bool   $delete_root_dir True to delete $dirname too, false to delete only the contents of $dirname.
	 */
	private static function rmdir_recursive( $dirname, bool $delete_root_dir ) {
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
