<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\UpdateDetection;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the UpdateDetection class.
 */
class UpdateDetectionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var UpdateDetection
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new UpdateDetection();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ) );

		if ( ! class_exists( \WP_Upgrader::class ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * @testdox Should report no update in progress by default.
	 */
	public function test_no_update_in_progress_by_default(): void {
		$this->assertFalse( $this->sut->is_update_in_progress(), 'No update signal should be active on a plain request' );
	}

	/**
	 * @testdox Should report an update in progress after upgrader_pre_install fires for WooCommerce.
	 */
	public function test_update_in_progress_after_pre_install_for_woocommerce(): void {
		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$response = apply_filters( 'upgrader_pre_install', true, array( 'plugin' => plugin_basename( WC_PLUGIN_FILE ) ) );

		$this->assertTrue( $response, 'The filter should return the response unmodified' );
		$this->assertTrue( $this->sut->is_update_in_progress(), 'A WooCommerce pre-install should activate the update window' );
	}

	/**
	 * @testdox Should not report an update in progress when another plugin is updated.
	 */
	public function test_no_update_in_progress_for_other_plugin(): void {
		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		apply_filters( 'upgrader_pre_install', true, array( 'plugin' => 'some-plugin/some-plugin.php' ) );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action(
			'upgrader_process_complete',
			new \WP_Upgrader(),
			array(
				'type'    => 'plugin',
				'action'  => 'update',
				'plugins' => array( 'some-plugin/some-plugin.php' ),
			)
		);

		$this->assertFalse( $this->sut->is_update_in_progress(), 'Updates of other plugins should not activate the update window' );
	}

	/**
	 * @testdox Should report an update in progress after a bulk upgrader_process_complete that includes WooCommerce.
	 */
	public function test_update_in_progress_after_bulk_process_complete(): void {
		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action(
			'upgrader_process_complete',
			new \WP_Upgrader(),
			array(
				'type'    => 'plugin',
				'action'  => 'update',
				'bulk'    => true,
				'plugins' => array( 'some-plugin/some-plugin.php', plugin_basename( WC_PLUGIN_FILE ) ),
			)
		);

		$this->assertTrue( $this->sut->is_update_in_progress(), 'A bulk update including WooCommerce should activate the update window' );
	}

	/**
	 * @testdox Should not report an update in progress for theme updates.
	 */
	public function test_no_update_in_progress_for_theme_update(): void {
		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action(
			'upgrader_process_complete',
			new \WP_Upgrader(),
			array(
				'type'   => 'theme',
				'action' => 'update',
				'themes' => array( 'storefront' ),
			)
		);

		$this->assertFalse( $this->sut->is_update_in_progress(), 'Theme updates should not activate the update window' );
	}

	/**
	 * @testdox Should report an update in progress in admin when the version on disk differs from the loaded code.
	 */
	public function test_update_in_progress_when_disk_version_differs_in_admin(): void {
		set_current_screen( 'dashboard' );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_file_data' => function ( $file, $headers, $context ) {
					// Avoid parameter not used PHPCS errors.
					unset( $file, $headers, $context );
					return array( 'Version' => '0.0.1' );
				},
			)
		);

		$this->assertTrue( $this->sut->is_update_in_progress(), 'A disk/loaded version mismatch in admin should activate the update window' );
	}

	/**
	 * @testdox Should not report an update in progress in admin when the version on disk matches the loaded code.
	 */
	public function test_no_update_in_progress_when_disk_version_matches_in_admin(): void {
		set_current_screen( 'dashboard' );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_file_data' => function ( $file, $headers, $context ) {
					// Avoid parameter not used PHPCS errors.
					unset( $file, $headers, $context );
					return array( 'Version' => WC()->version );
				},
			)
		);

		$this->assertFalse( $this->sut->is_update_in_progress(), 'A matching disk version should not activate the update window' );
	}

	/**
	 * @testdox Should treat an unreadable version on disk as an update in progress.
	 */
	public function test_update_in_progress_when_disk_version_unreadable(): void {
		set_current_screen( 'dashboard' );
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_file_data' => function ( $file, $headers, $context ) {
					// Avoid parameter not used PHPCS errors.
					unset( $file, $headers, $context );
					return array( 'Version' => '' );
				},
			)
		);

		$this->assertTrue( $this->sut->is_update_in_progress(), 'An unreadable disk version should be treated as an update window' );
	}

	/**
	 * @testdox Should skip the disk version check outside of admin requests.
	 */
	public function test_disk_version_check_skipped_outside_admin(): void {
		$get_file_data_calls = 0;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_file_data' => function () use ( &$get_file_data_calls ) {
					++$get_file_data_calls;
					return array( 'Version' => '0.0.1' );
				},
			)
		);

		$this->assertFalse( $this->sut->is_update_in_progress(), 'The disk version check should not run on frontend requests' );
		$this->assertSame( 0, $get_file_data_calls, 'get_file_data should not be called outside admin' );
	}

	/**
	 * @testdox Should read the version on disk only once per request.
	 */
	public function test_disk_version_check_is_memoized(): void {
		set_current_screen( 'dashboard' );
		$get_file_data_calls = 0;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_file_data' => function () use ( &$get_file_data_calls ) {
					++$get_file_data_calls;
					return array( 'Version' => '0.0.1' );
				},
			)
		);

		$this->sut->is_update_in_progress();
		$this->sut->is_update_in_progress();
		$this->sut->is_update_in_progress();

		$this->assertSame( 1, $get_file_data_calls, 'The disk version should be read at most once per request' );
	}

	/**
	 * @testdox Should log suppressed work once per context per throttle window.
	 */
	public function test_log_suppressed_work_is_throttled_per_context(): void {
		$fake_logger = $this->create_fake_logger();
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		$this->sut->log_suppressed_work( 'test_context_one' );
		$this->sut->log_suppressed_work( 'test_context_one' );
		$this->sut->log_suppressed_work( 'test_context_two' );

		$this->assertCount( 2, $fake_logger->warnings, 'Repeated logging for the same context should be throttled' );
	}

	/**
	 * @testdox Should include the caught error details when logging failed work.
	 */
	public function test_log_suppressed_work_includes_throwable_details(): void {
		$fake_logger = $this->create_fake_logger();
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		$this->sut->log_suppressed_work( 'test_context_error', new \Error( 'Class "Foo" not found' ) );

		$this->assertCount( 1, $fake_logger->warnings );
		$this->assertStringContainsString( 'Class "Foo" not found', $fake_logger->warnings[0]['message'] );
		$this->assertSame( \Error::class, $fake_logger->warnings[0]['context']['error']['class'] );
	}

	/**
	 * Create a minimal fake logger that records warning calls.
	 *
	 * @return object The fake logger.
	 */
	private function create_fake_logger() {
		// phpcs:ignore Squiz.Commenting
		return new class() {
			/**
			 * Recorded warning calls.
			 *
			 * @var array
			 */
			public $warnings = array();

			/**
			 * Record a warning call.
			 *
			 * @param string $message The log message.
			 * @param array  $context The log context.
			 */
			public function warning( $message, $context = array() ) {
				$this->warnings[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}
}
