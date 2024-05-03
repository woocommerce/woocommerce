<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\{ LogHandlerFileV2, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;
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
		$message = <<<MESSAGE
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
		$this->assertCount( 2, $paths ); // New log gets created when old logs are deleted!

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '3 log files from source <code>duck</code> were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
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
		$this->assertCount( 3, $paths ); // New log gets created when old logs are deleted!

		$paths = glob( Settings::get_log_directory() . 'wc_logger*.log' );
		$this->assertCount( 1, $paths );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content  = file_get_contents( reset( $paths ) );
		$expected_string = '4 expired log files were deleted.';
		$this->assertStringContainsString( $expected_string, $actual_content );
	}
}
