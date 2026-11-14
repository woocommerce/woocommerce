<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\{ LogHandlerFileV2, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ File, FileController };
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WC_Unit_Test_Case;

/**
 * LogHandlerFileV2Test class.
 */
class LogHandlerFileV2Test extends WC_Unit_Test_Case {
	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var LogHandlerFileV2
	 */
	private $sut;

	/**
	 * Set up to do before running any of these tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::delete_all_log_files();
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new LogHandlerFileV2();
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		self::delete_all_log_files();
		parent::tearDown();
	}

	/**
	 * Delete all existing log files.
	 *
	 * @return void
	 */
	private static function delete_all_log_files(): void {
		$files = glob( Settings::get_log_directory() . '*.log' );
		foreach ( $files as $file ) {
			unlink( $file );
		}
	}

	/**
	 * Data provider for test_handle_created_filenames.
	 *
	 * @return iterable
	 */
	public function provide_created_filenames_data(): iterable {
		$current_time = time();
		$past_time    = strtotime( '-2 days' );

		yield 'no source, current time' => array(
			array(
				'timestamp' => $current_time,
				'context'   => array(),
			),
			'plugin-woocommerce-' . gmdate( 'Y-m-d', $current_time ),
		);
		yield 'custom source, past time' => array(
			array(
				'timestamp' => $past_time,
				'context'   => array( 'source' => 'tater_tots' ),
			),
			'tater_tots-' . gmdate( 'Y-m-d', $past_time ),
		);
		yield 'custom source with formatting issues, current time' => array(
			array(
				'timestamp' => $current_time,
				'context'   => array( 'source' => 'MACARONI & chEEse_Puffs' ),
			),
			'macaroni-cheese_puffs-' . gmdate( 'Y-m-d', $current_time ),
		);
	}

	/**
	 * @testdox Check that the handle method creates consistent filenames.
	 *
	 * @dataProvider provide_created_filenames_data
	 *
	 * @param array  $input    Arguments for the handle method.
	 * @param string $expected The expected first part of the created filename.
	 */
	public function test_handle_created_filenames( array $input, string $expected ): void {
		$this->sut->handle(
			$input['timestamp'],
			'debug',
			'test',
			$input['context']
		);

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		$parsed = File::parse_path( reset( $paths ) );

		$this->assertStringStartsWith( $expected, $parsed['basename'] );
	}

	/**
	 * @testdox Check that the handle method formats the message content correctly.
	 */
	public function test_handle_message_formatting() {
		$time    = time();
		$message = <<<'MESSAGE'
How to win
1. Bake cookies
2. ???
3. Profit
MESSAGE;
		$message = trim( $message );

		$this->sut->handle(
			$time,
			'debug',
			$message,
			array()
		);

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content   = file_get_contents( reset( $paths ) );
		$expected_content = gmdate( 'c', $time ) . ' DEBUG ' . $message . "\n";

		$this->assertEquals( $expected_content, $actual_content );
	}

	/**
	 * Mock backtrace data.
	 *
	 * @return array[]
	 */
	private function get_mock_backtrace() {
		return array(
			array(
				'file'     => 'foo.bar',
				'line'     => 1337,
				'function' => 'baz',
			),
		);
	}

	/**
	 * Data provider for test_handle_context_output.
	 *
	 * @return iterable
	 */
	public function provide_context(): iterable {
		$context_delineator = ' CONTEXT: ';

		yield 'no context' => array(
			array(),
			'',
		);
		yield 'source only' => array(
			array( 'source' => 'frootloops' ),
			'',
		);
		yield 'source and custom keys' => array(
			array(
				'source' => 'frootloops',
				'yin'    => 'yang',
				'apple'  => 'orange',
			),
			$context_delineator . '{"yin":"yang","apple":"orange"}',
		);
		yield 'custom keys with multibyte and slashed values' => array(
			array(
				'multibyte'   => '中文字',
				'backslashes' => 'C:\MS-DOS\\',
			),
			$context_delineator . '{"multibyte":"中文字","backslashes":"C:\\\\MS-DOS\\\\"}',
		);
		yield 'backtrace boolean only' => array(
			array( 'backtrace' => true ),
			$context_delineator . wp_json_encode( array( 'backtrace' => $this->get_mock_backtrace() ) ),
		);
		yield 'backtrace custom value' => array(
			array( 'backtrace' => 'Not actually a backtrace' ),
			$context_delineator . '{"backtrace":"Not actually a backtrace"}',
		);
	}

	/**
	 * @testdox Check that various data provided to the handler in the context arg is rendered correctly.
	 *
	 * @dataProvider provide_context
	 *
	 * @param array  $input    Arguments for the handle method.
	 * @param string $expected The expected content appended to the log entry.
	 */
	public function test_handle_context_output( array $input, string $expected ): void {
		// Mock the backtrace output.
		$handler = new class() extends LogHandlerFileV2 {
			// phpcs:ignore Squiz.Commenting.VariableComment.Missing
			protected $backtrace_data;

			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function set_backtrace_data( $data ) {
				$this->backtrace_data = $data;
			}

			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected static function get_backtrace() {
				return array(
					array(
						'file'     => 'foo.bar',
						'line'     => 1337,
						'function' => 'baz',
					),
				);
			}
		};
		$handler->set_backtrace_data( $this->get_mock_backtrace() );

		$time    = time();
		$message = 'Schmaltz';

		$handler->handle(
			$time,
			'debug',
			$message,
			$input,
		);

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_prefix = gmdate( 'c', $time ) . ' DEBUG ' . $message;

		$this->assertEquals( $expected_prefix . $expected . "\n", $actual_content );
	}

	/**
	 * Data provider for test_handle_context_is_valid_json.
	 *
	 * Only values whose types survive a JSON round trip belong here: a zero-fraction
	 * float like 1.0 encodes to 1 and decodes back as an integer, failing assertSame().
	 *
	 * @return array
	 */
	public function provide_context_values(): array {
		return array(
			'namespaced class name' => array( array( 'class' => 'Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2' ) ),
			'windows path'          => array( array( 'path' => 'C:\Windows\System32' ) ),
			'double quotes'         => array( array( 'quote' => 'He said "hi" to "you"' ) ),
			'newlines and tabs'     => array( array( 'multi' => "line1\nline2\ttab" ) ),
			'multibyte characters'  => array( array( 'text' => '中文字 café 🎉' ) ),
			'mixed scalar types'    => array(
				array(
					'i' => 7,
					'f' => 3.14,
					'b' => true,
					'z' => null,
				),
			),
			'combined'              => array(
				array(
					'class' => 'Automattic\WooCommerce\Foo',
					'url'   => 'https://example.com/x',
					'quote' => 'He said "hi"',
				),
			),
		);
	}

	/**
	 * @testdox A log entry's CONTEXT is valid JSON that decodes back to the original context values.
	 *
	 * @dataProvider provide_context_values
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/62830
	 *
	 * @param array $context The context values to log, excluding the source.
	 */
	public function test_handle_context_is_valid_json( array $context ): void {
		$this->sut->handle(
			time(),
			'debug',
			'Test log entry.',
			array_merge( array( 'source' => 'test' ), $context )
		);

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( reset( $paths ) );
		$this->assertStringContainsString( ' CONTEXT: ', $content );

		// The handler appends the context as JSON after " CONTEXT: ".
		$json    = explode( ' CONTEXT: ', $content, 2 )[1];
		$decoded = json_decode( trim( $json ), true );

		$this->assertSame( $context, $decoded );
	}

	/**
	 * @testdox Check that the delete_logs_before_timestamp method deletes files based on their created date.
	 */
	public function test_clear() {
		$this->sut->handle( time(), 'debug', 'duck', array( 'source' => 'duck' ) );
		$this->sut->handle( strtotime( '-2 days' ), 'debug', 'duck', array( 'source' => 'duck' ) );
		$this->sut->handle( strtotime( '-4 days' ), 'debug', 'duck', array( 'source' => 'duck' ) );
		$this->sut->handle( time(), 'debug', 'goose', array( 'source' => 'goose' ) );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 4, $paths );

		$result = $this->sut->clear( 'duck' );
		$this->assertEquals( 3, $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 2, $paths );
		// New log gets created when old logs are deleted!

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '3 log files from source <code>duck</code> were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}

	/**
	 * @testdox Check that clear deletes more than the default per-page of log files from a source in one run.
	 */
	public function test_clear_deletes_more_than_default_per_page() {
		// Create more files for a single source than a single delete batch can
		// remove (batch size is 100), using a distinct date per file.
		$expired_count = 101;
		foreach ( range( 1, $expired_count ) as $days_ago ) {
			$this->sut->handle( strtotime( "-{$days_ago} days" ), 'debug', 'quack.', array( 'source' => 'duck' ) );
		}

		// Add a couple of files from a different source that must be left alone.
		$this->sut->handle( time(), 'debug', 'honk.', array( 'source' => 'goose' ) );
		$this->sut->handle( strtotime( '-1 day' ), 'debug', 'honk.', array( 'source' => 'goose' ) );

		$paths = glob( Settings::get_log_directory() . 'duck*.log' );
		$this->assertCount( $expired_count, $paths );

		$result = $this->sut->clear( 'duck' );
		$this->assertEquals( $expired_count, $result );

		$paths = glob( Settings::get_log_directory() . 'duck*.log' );
		$this->assertCount( 0, $paths );

		// The two goose files remain untouched.
		$paths = glob( Settings::get_log_directory() . 'goose*.log' );
		$this->assertCount( 2, $paths );

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '101 log files from source <code>duck</code> were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}

	/**
	 * @testdox Check that clear terminates and deletes everything when a source has exactly the batch-size number of files.
	 */
	public function test_clear_deletes_exactly_one_full_batch() {
		// 100 files is exactly DELETE_BATCH_SIZE: the loop fetches a full page, then
		// must make one more (empty) fetch and break rather than miscount or hang.
		$total = 100;
		foreach ( range( 1, $total ) as $days_ago ) {
			$this->sut->handle( strtotime( "-{$days_ago} days" ), 'debug', 'quack.', array( 'source' => 'duck' ) );
		}

		$this->assertCount( $total, glob( Settings::get_log_directory() . 'duck*.log' ) );

		$result = $this->sut->clear( 'duck' );

		$this->assertEquals( $total, $result );
		$this->assertCount( 0, glob( Settings::get_log_directory() . 'duck*.log' ) );
	}

	/**
	 * @testdox Check that clear removes every deletable file for a source even when a full batch contains undeletable files.
	 */
	public function test_clear_removes_all_deletable_files_when_a_full_batch_contains_undeletable_files() {
		$total = 150;
		foreach ( range( 1, $total ) as $days_ago ) {
			$this->sut->handle( strtotime( "-{$days_ago} days" ), 'debug', 'quack.', array( 'source' => 'duck' ) );
		}

		/*
		 * Give every file the same modified time. This is the tie condition that
		 * makes the default 'modified' sort non-deterministic on PHP < 8.0 (unstable
		 * usort). clear() pages by 'created' instead, which is unique per file for a
		 * single source, so the batch layout stays deterministic and offset paging
		 * never strands a deletable file behind an undeletable one.
		 */
		$filesystem = FilesystemUtil::get_wp_filesystem();
		$paths      = glob( Settings::get_log_directory() . 'duck*.log' );
		$this->assertCount( $total, $paths );
		$shared_mtime = time();
		foreach ( $paths as $path ) {
			$filesystem->touch( $path, $shared_mtime );
		}
		// glob() sorts oldest-date first; reverse to created-descending, the order clear() pages in.
		$newest_first = array_reverse( $paths );

		/*
		 * Lock files at adversarial positions: inside the first full batch (0, 50, 99),
		 * on the batch boundary (100), and in the trailing partial batch (149). If clear()
		 * advanced its offset past a deletable file while skipping an undeletable one, one of
		 * the non-locked files would survive and the deleted count would fall short.
		 */
		$locked_indexes = array( 0, 50, 99, 100, 149 );
		$locked_ids     = array();
		foreach ( $locked_indexes as $index ) {
			$locked_ids[] = ( new File( $newest_first[ $index ] ) )->get_file_id();
		}

		$controller = new class( $locked_ids ) extends FileController {
			/**
			 * File IDs that delete_files() must refuse to delete, simulating files that
			 * cannot be removed from disk (e.g. a permission error).
			 *
			 * @var string[]
			 */
			private $locked_ids;

			/**
			 * Constructor.
			 *
			 * @param string[] $locked_ids File IDs that must not be deleted.
			 */
			public function __construct( array $locked_ids ) {
				// FileController declares no constructor, so there is intentionally no parent::__construct() call.
				$this->locked_ids = $locked_ids;
			}

			/**
			 * Delete every requested file except the locked ones.
			 *
			 * @param string[] $file_ids The file IDs to delete.
			 *
			 * @return int The number of files that were deleted.
			 */
			public function delete_files( array $file_ids ): int {
				return parent::delete_files( array_values( array_diff( $file_ids, $this->locked_ids ) ) );
			}
		};

		$property = new \ReflectionProperty( LogHandlerFileV2::class, 'file_controller' );
		$property->setAccessible( true );
		$property->setValue( $this->sut, $controller );

		$result = $this->sut->clear( 'duck' );

		$this->assertEquals( $total - count( $locked_indexes ), $result, 'Every deletable file for the source should be removed.' );

		$remaining = glob( Settings::get_log_directory() . 'duck*.log' );
		sort( $remaining );
		$expected_remaining = array();
		foreach ( $locked_indexes as $index ) {
			$expected_remaining[] = $newest_first[ $index ];
		}
		sort( $expected_remaining );
		$this->assertSame( $expected_remaining, $remaining, 'Only the undeletable files should remain on disk.' );
	}

	/**
	 * @testdox Check that clear only deletes files for the exact source, not sources that merely share its prefix.
	 */
	public function test_clear_only_deletes_the_exact_source() {
		$this->sut->handle( time(), 'debug', 'a', array( 'source' => 'foo' ) );
		$this->sut->handle( time(), 'debug', 'b', array( 'source' => 'foo-two' ) );
		$this->sut->handle( time(), 'debug', 'c', array( 'source' => 'foobar' ) );

		$this->assertCount( 3, glob( Settings::get_log_directory() . '*.log' ) );

		$result = $this->sut->clear( 'foo' );

		$this->assertEquals( 1, $result, 'clear() should delete only the file belonging to the exact source.' );
		$this->assertCount( 1, glob( Settings::get_log_directory() . 'foo-two-*.log' ), 'The foo-two source must be left untouched.' );
		$this->assertCount( 1, glob( Settings::get_log_directory() . 'foobar-*.log' ), 'The foobar source must be left untouched.' );
	}

	/**
	 * @testdox Check that clear deletes rotated files of the exact source while leaving a prefix-sibling source alone.
	 */
	public function test_clear_deletes_rotated_files_of_the_exact_source() {
		$file_controller = wc_get_container()->get( FileController::class );

		// Log to 'foo', rotate that file, then log again so there is a current file plus
		// a rotation of it. Both parse to source 'foo'.
		$this->sut->handle( time(), 'debug', 'first', array( 'source' => 'foo' ) );
		$foo = $file_controller->get_files(
			array(
				'source'       => 'foo',
				'exact_source' => true,
			)
		);
		$foo[0]->rotate();
		$this->sut->handle( time(), 'debug', 'second', array( 'source' => 'foo' ) );

		// A prefix sibling that must survive.
		$this->sut->handle( time(), 'debug', 'sibling', array( 'source' => 'foo-two' ) );

		$this->assertCount(
			2,
			$file_controller->get_files(
				array(
					'source'       => 'foo',
					'exact_source' => true,
				)
			),
			'Setup: expected the current file and one rotation for source foo.'
		);

		$result = $this->sut->clear( 'foo' );

		$this->assertEquals( 2, $result, 'clear() should delete the current and rotated files of the exact source.' );
		$this->assertCount(
			0,
			$file_controller->get_files(
				array(
					'source'       => 'foo',
					'exact_source' => true,
				)
			),
			'All foo files, including rotations, should be gone.'
		);
		$this->assertCount( 1, glob( Settings::get_log_directory() . 'foo-two-*.log' ), 'The foo-two source must be left untouched.' );
	}

	/**
	 * @testdox Check that clear does nothing when given a source that sanitizes to an empty string.
	 */
	public function test_clear_does_nothing_for_an_empty_source() {
		$this->sut->handle( time(), 'debug', 'a', array( 'source' => 'keep-me' ) );
		$this->sut->handle( time(), 'debug', 'b', array( 'source' => 'keep-me-too' ) );

		$this->assertCount( 2, glob( Settings::get_log_directory() . '*.log' ) );

		$result = $this->sut->clear( '' );

		$this->assertEquals( 0, $result, 'clear() must not delete anything for an empty source.' );
		$this->assertCount( 2, glob( Settings::get_log_directory() . '*.log' ), 'No log files should be deleted for an empty source.' );
	}

	/**
	 * @testdox Check that the delete_logs_before_timestamp method deletes files based on their created date.
	 */
	public function test_delete_logs_before_timestamp() {
		$current_time = time();
		$past_time    = strtotime( '-5 days' );

		$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => 'source1' ) );
		$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => 'source2' ) );
		$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => 'source3' ) );
		$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => 'source4' ) );
		$this->sut->handle( $current_time, 'debug', 'new!', array( 'source' => 'source5' ) );
		$this->sut->handle( $current_time, 'debug', 'new!', array( 'source' => 'source6' ) );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 6, $paths );

		$result = $this->sut->delete_logs_before_timestamp( strtotime( '-3 days' ) );
		$this->assertEquals( 4, $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 3, $paths );
		// New log gets created when old logs are deleted!

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '4 expired log files were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}

	/**
	 * @testdox Check that delete_logs_before_timestamp deletes more than 20 expired log files in one run.
	 */
	public function test_delete_logs_before_timestamp_deletes_more_than_default_per_page() {
		$current_time = time();
		$past_time    = strtotime( '-5 days' );

		// Create more expired files than a single delete batch can remove (batch size is 100).
		$expired_count = 101;
		foreach ( range( 1, $expired_count ) as $suffix ) {
			$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => "source-{$suffix}" ) );
		}

		// Add a couple of non-expired files as well.
		$this->sut->handle( $current_time, 'debug', 'new!', array( 'source' => 'fresh1' ) );
		$this->sut->handle( $current_time, 'debug', 'new!', array( 'source' => 'fresh2' ) );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( $expired_count + 2, $paths );

		$result = $this->sut->delete_logs_before_timestamp( strtotime( '-3 days' ) );
		$this->assertEquals( $expired_count, $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		// wc_logger plus two fresh files.
		$this->assertCount( 3, $paths );

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '101 expired log files were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}

	/**
	 * @testdox Check that delete_logs_before_timestamp advances past a fully vetoed batch.
	 */
	public function test_delete_logs_before_timestamp_advances_past_vetoed_batch() {
		$current_time = time();
		$past_time    = strtotime( '-5 days' );

		// Create 100 vetoed files, then the single allowed file. Files are sorted
		// by modified time descending, so the 100 vetoed files normally land on the
		// first 100-file page. Touch the allowed file to an older mtime so it is
		// guaranteed to fall on the next page and forces pagination to advance past
		// a fully vetoed batch.
		$expired_count = 100;
		foreach ( range( 1, $expired_count ) as $suffix ) {
			$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => "source-{$suffix}" ) );
		}
		$this->sut->handle( $past_time, 'debug', 'old.', array( 'source' => 'source-101' ) );

		$allowed_paths = glob( Settings::get_log_directory() . '*source-101*.log' );
		$this->assertCount( 1, $allowed_paths );
		FilesystemUtil::get_wp_filesystem()->touch( reset( $allowed_paths ), $past_time - 1 );

		// Allow only the older file to be deleted.
		$filter = function ( $delete, $file ) {
			unset( $delete );
			$basename = basename( $file->get_path() );
			return false !== strpos( $basename, 'source-101' );
		};
		add_filter( 'woocommerce_logger_delete_expired_file', $filter, 10, 2 );

		try {
			$result = $this->sut->delete_logs_before_timestamp( strtotime( '-3 days' ) );
		} finally {
			remove_filter( 'woocommerce_logger_delete_expired_file', $filter, 10 );
		}

		$this->assertEquals( 1, $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		// 100 vetoed files, 1 wc_logger summary file.
		$this->assertCount( 101, $paths );

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '1 expired log file was deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}

	/**
	 * @testdox Check that the handle method does not throw an error when passed a non-array context.
	 */
	public function test_handle_context_does_not_throw_error_non_array_contexts() {
		$result = $this->sut->handle( time(), 'debug', 'test', 'not an array' );
		$this->assertTrue( $result );

		$result = $this->sut->handle( time(), 'debug', 'test', null );
		$this->assertTrue( $result );

		$result = $this->sut->handle( time(), 'debug', 'test', 42 );
		$this->assertTrue( $result );

		$result = $this->sut->handle( time(), 'debug', 'test', new \WC_Order() );
		$this->assertTrue( $result );
	}
}
