<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\{ LogHandlerFileV2, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use WC_Logger;
use WC_Log_Handler_DB, WC_Log_Handler_Email;
use WC_Unit_Test_Case;

/**
 * SettingsTest class.
 */
class SettingsTest extends WC_Unit_Test_Case {
	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Instance of the WC_Logger class.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Instance of the FileController class.
	 *
	 * @var FileController
	 */
	private $file_controller;

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
		$this->sut             = new Settings();
		$this->logger          = new WC_Logger();
		$this->file_controller = new FileController();
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
		$upload_dir    = wp_upload_dir( null, false );
		$log_directory = $upload_dir['basedir'] . '/wc-logs/';

		$files = glob( $log_directory . '*.log' );
		foreach ( $files as $file ) {
			unlink( $file );
		}
	}

	/**
	 * @testdox Check that the get_log_directory method returns the correct directory path.
	 */
	public function test_get_log_directory_path(): void {
		Constants::set_constant( 'WC_LOG_DIR_CUSTOM', true );
		Constants::set_constant( 'WC_LOG_DIR', '/a/test/path/constant' );
		$actual = Settings::get_log_directory();
		$this->assertEquals( '/a/test/path/constant/', $actual );
		Constants::clear_constants();

		$callback = fn() => '/a/test/path/filter';
		add_filter( 'woocommerce_log_directory', $callback );

		$actual = Settings::get_log_directory();
		$this->assertEquals( '/a/test/path/filter/', $actual );

		remove_filter( 'woocommerce_log_directory', $callback );

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => '/a/test/path' ),
			)
		);
		$actual = Settings::get_log_directory();
		$this->assertEquals( '/a/test/path/wc-logs/', $actual );
		$this->reset_legacy_proxy_mocks();
	}

	/**
	 * @testdox Check that the get_log_directory method creates the directory if it doesn't exist yet.
	 */
	public function test_get_log_directory_creation() {
		$upload_dir = wp_upload_dir();
		$path       = $upload_dir['basedir'] . '/wc-logs-test/';

		$callback = fn() => $path;
		add_filter( 'woocommerce_log_directory', $callback );

		// First check that the test directory doesn't exist yet.
		$this->assertFalse( wp_is_writable( $path ) );

		// Then test that the $initialize param works when set to false.
		$actual_path = Settings::get_log_directory( false );
		$this->assertEquals( $path, $actual_path );
		$this->assertFalse( wp_is_writable( $actual_path ) );

		// Finally test directory creation.
		$actual_path = Settings::get_log_directory();
		$this->assertEquals( $path, $actual_path );
		$this->assertTrue( wp_is_writable( $actual_path ) );

		remove_filter( 'woocommerce_log_directory', $callback );

		// The GLOB_BRACE flag is not available in some environments.
		$files = array_merge(
			glob( $path . '*' ),
			glob( $path . '.[!.,!..]*' )
		);
		$this->assertCount( 2, $files );
		$this->assertContains( $path . '.htaccess', $files );
		$this->assertContains( $path . 'index.html', $files );

		foreach ( $files as $file ) {
			unlink( $file );
		}
		rmdir( $path );
	}

	/**
	 * @testdox Check that log entries can be recorded while logging is enabled.
	 */
	public function test_logs_created_while_logging_enabled(): void {
		$this->assertTrue( $this->sut->logging_is_enabled() );

		$files = $this->file_controller->get_files();
		$this->assertCount( 0, $files );

		$this->logger->debug( 'Test' );

		$files = $this->file_controller->get_files();
		$this->assertCount( 1, $files );
	}

	/**
	 * @testdox Check that log entries cannot be recorded while logging is disabled.
	 */
	public function test_logs_not_created_while_logging_disabled(): void {
		update_option( 'woocommerce_logs_logging_enabled', 'no' );
		$this->assertFalse( $this->sut->logging_is_enabled() );

		$files = $this->file_controller->get_files();
		$this->assertCount( 0, $files );

		$this->logger->debug( 'Test' );

		$files = $this->file_controller->get_files();
		$this->assertCount( 0, $files );

		delete_option( 'woocommerce_logs_logging_enabled' );
	}

	/**
	 * @testdox Check that the get_default_handler method returns the default value when nothing else has been set.
	 */
	public function test_default_handler_setting_default_value(): void {
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( LogHandlerFileV2::class, $handler );
	}

	/**
	 * @testdox Check that the get_default_handler method returns the value set in WC_LOG_HANDLER as long as it's a
	 *          valid handler class.
	 */
	public function test_default_handler_setting_with_constant(): void {
		Constants::set_constant( 'WC_LOG_HANDLER', WC_Log_Handler_DB::class );
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( WC_Log_Handler_DB::class, $handler );

		// Invalid handler.
		Constants::set_constant( 'WC_LOG_HANDLER', 'Fluffernutter' );
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( LogHandlerFileV2::class, $handler );

		Constants::clear_single_constant( 'WC_LOG_HANDLER' );
	}

	/**
	 * @testdox Check that the get_default_handler method returns the value set in the options table as long as it's a
	 *          valid handler class.
	 */
	public function test_default_handler_setting_with_option_value(): void {
		update_option( 'woocommerce_logs_default_handler', WC_Log_Handler_Email::class );
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( WC_Log_Handler_Email::class, $handler );

		// Constant overrides option.
		Constants::set_constant( 'WC_LOG_HANDLER', WC_Log_Handler_DB::class );
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( WC_Log_Handler_DB::class, $handler );
		Constants::clear_single_constant( 'WC_LOG_HANDLER' );

		// Invalid handler.
		update_option( 'woocommerce_logs_default_handler', 'Smorgasbord' );
		$handler = $this->sut->get_default_handler();
		$this->assertEquals( LogHandlerFileV2::class, $handler );

		delete_option( 'woocommerce_logs_default_handler' );
	}

	/**
	 * @testdox Check that the get_retention_period method returns the default value when nothing else has been set.
	 */
	public function test_retention_period_setting_default_value(): void {
		$retention = $this->sut->get_retention_period();
		$this->assertEquals( 30, $retention );
	}

	/**
	 * @testdox Check that the get_retention_period method returns the value set by the filter callback as long as
	 *          it's a valid value.
	 */
	public function test_retention_period_setting_with_filter(): void {
		add_filter( 'woocommerce_logger_days_to_retain_logs', fn() => 45 );
		$retention = $this->sut->get_retention_period();
		$this->assertEquals( 45, $retention );
		remove_all_filters( 'woocommerce_logger_days_to_retain_logs' );

		// Invalid number.
		add_filter( 'woocommerce_logger_days_to_retain_logs', '__return_zero' );
		$retention = $this->sut->get_retention_period();
		$this->assertEquals( 30, $retention );
		remove_all_filters( 'woocommerce_logger_days_to_retain_logs' );
	}

	/**
	 * @testdox Check that the get_retention_period method returns the value set in the options table as long as it's a
	 *           valid value.
	 */
	public function test_retention_period_setting_with_option_value(): void {
		update_option( 'woocommerce_logs_retention_period_days', 53 );
		$handler = $this->sut->get_retention_period();
		$this->assertEquals( 53, $handler );

		// Filter overrides option.
		add_filter( 'woocommerce_logger_days_to_retain_logs', fn() => 45 );
		$retention = $this->sut->get_retention_period();
		$this->assertEquals( 45, $retention );
		remove_all_filters( 'woocommerce_logger_days_to_retain_logs' );

		// Invalid number.
		update_option( 'woocommerce_logs_retention_period_days', 'french toast' );
		$handler = $this->sut->get_retention_period();
		$this->assertEquals( 30, $handler );

		delete_option( 'woocommerce_logs_retention_period_days' );
	}

	/**
	 * @testdox Check that the get_level_threshold method returns the default value when nothing else has been set.
	 */
	public function test_level_threshold_setting_default_value(): void {
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'none', $level );
	}

	/**
	 * @testdox Check that the get_level_threshold method returns the value set in WC_LOG_THRESHOLD as long as it's a
	 *          valid level.
	 */
	public function test_level_threshold_setting_with_constant(): void {
		Constants::set_constant( 'WC_LOG_THRESHOLD', 'alert' );
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'alert', $level );

		// Invalid level.
		Constants::set_constant( 'WC_LOG_THRESHOLD', 'mascarpone' );
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'none', $level );

		Constants::clear_single_constant( 'WC_LOG_THRESHOLD' );
	}

	/**
	 * @testdox Check that the get_level_threshold method returns the value set in the options table as long as it's a
	 *          valid level.
	 */
	public function test_level_threshold_setting_with_option_value(): void {
		update_option( 'woocommerce_logs_level_threshold', 'warning' );
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'warning', $level );

		// Constant overrides option.
		Constants::set_constant( 'WC_LOG_THRESHOLD', 'info' );
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'info', $level );
		Constants::clear_single_constant( 'WC_LOG_THRESHOLD' );

		// Invalid level.
		update_option( 'woocommerce_logs_level_threshold', 123 );
		$level = $this->sut->get_level_threshold();
		$this->assertEquals( 'none', $level );

		delete_option( 'woocommerce_logs_level_threshold' );
	}

	/**
	 * @testdox Check that the settings form does not contain other settings controls when logging is disabled.
	 */
	public function test_render_form_logging_disabled_no_other_settings(): void {
		update_option( 'woocommerce_logs_logging_enabled', 'no' );

		ob_start();
		$this->sut->render_form();
		$content = ob_get_clean();

		$this->assertStringNotContainsString( 'Log storage', $content );
		$this->assertStringNotContainsString( 'Retention period', $content );
		$this->assertStringNotContainsString( 'Level threshold', $content );

		delete_option( 'woocommerce_logs_logging_enabled' );
	}

	/**
	 * @testdox Check that the settings form default handler control is disabled when the WC_LOG_HANDLER constant is set.
	 */
	public function test_render_form_default_handler_input_disabled_with_constant(): void {
		Constants::set_constant( 'WC_LOG_HANDLER', WC_Log_Handler_DB::class );

		ob_start();
		$this->sut->render_form();
		$content = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/name="woocommerce_logs_default_handler"[^>]+disabled/',
			$content
		);
		$this->assertStringContainsString(
			'This setting cannot be changed here because it is defined in the <code>WC_LOG_HANDLER</code> constant',
			$content
		);

		Constants::clear_single_constant( 'WC_LOG_HANDLER' );
	}

	/**
	 * @testdox Check that the settings form retention period control is disabled when the
	 *          woocommerce_logger_days_to_retain_logs hook has a filter.
	 */
	public function test_render_form_retention_period_input_disabled_with_filter(): void {
		add_filter( 'woocommerce_logger_days_to_retain_logs', fn() => 45 );

		ob_start();
		$this->sut->render_form();
		$content = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/name="woocommerce_logs_retention_period_days"[^>]+disabled/',
			$content
		);
		$this->assertStringContainsString(
			'This setting cannot be changed here because it is being set by a filter on the <code>woocommerce_logger_days_to_retain_logs</code> hook',
			$content
		);

		remove_all_filters( 'woocommerce_logger_days_to_retain_logs' );
	}

	/**
	 * @testdox Check that the settings form level threshold control is disabled when the WC_LOG_THRESHOLD constant is set.
	 */
	public function test_render_form_level_threshold_input_disabled_with_constant(): void {
		Constants::set_constant( 'WC_LOG_THRESHOLD', 'error' );

		ob_start();
		$this->sut->render_form();
		$content = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/name="woocommerce_logs_level_threshold"[^>]+disabled/',
			$content
		);
		$this->assertStringContainsString(
			'This setting cannot be changed here because it is defined in the <code>WC_LOG_THRESHOLD</code> constant',
			$content
		);

		Constants::clear_single_constant( 'WC_LOG_THRESHOLD' );
	}
}
