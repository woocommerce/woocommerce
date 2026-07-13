<?php
/**
 * Unit tests for the WC_Tracker class.
 *
 * @package WooCommerce\Tests\WC_Tracker.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\PluginUtil;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Ignoring test doubles.

/**
 * Mock Address Provider for testing.
 */
class WC_Tracker_Test_MockAddressProvider extends WC_Address_Provider {
	/**
	 * Constructor.
	 *
	 * @param string $id   Provider ID.
	 * @param string $name Provider name.
	 */
	public function __construct( $id = 'mock-address-provider', $name = 'Mock Address Provider' ) {
		$this->id   = $id;
		$this->name = $name;
	}
}

/**
 * Class WC_Tracker_Test
 */
class WC_Tracker_Test extends \WC_Unit_Test_Case {
	// phpcs:enable

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_allow_tracking', 'yes' );
		delete_option( 'woocommerce_tracker_last_send' );
		as_unschedule_all_actions( 'woocommerce_tracker_send_event_attempt' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( 'woocommerce_tracker_send_event_attempt' );
		delete_option( 'woocommerce_tracker_last_send' );
		delete_option( 'woocommerce_allow_tracking' );

		parent::tearDown();
	}

	/**
	 * Test the tracking of wc_admin being disabled via filter.
	 */
	public function test_wc_admin_disabled_get_tracking_data() {
		$posted_data = null;

		// Test the case for woocommerce_admin_disabled filter returning true.
		add_filter(
			'woocommerce_admin_disabled',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( $default_value ) {
				return true;
			}
		);

		add_filter(
			'pre_http_request',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			function ( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return array( 'response' => array( 'code' => 204 ) );
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data_attempt();
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'yes', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * Test the tracking of wc_admin being not disabled via filter.
	 */
	public function test_wc_admin_not_disabled_get_tracking_data() {
		$posted_data = null;
		// Bypass time delay so we can invoke send_tracking_data again.
		update_option( 'woocommerce_tracker_last_send', strtotime( '-2 weeks' ) );

		add_filter(
			'pre_http_request',
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			function ( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return array( 'response' => array( 'code' => 204 ) );
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data_attempt();
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'no', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * @testdox Public sends enqueue one response-aware attempt without generating or posting data inline.
	 */
	public function test_send_tracking_data_enqueues_one_attempt_without_inline_work(): void {
		$data_filter_calls = 0;
		$http_calls        = 0;
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) use ( &$data_filter_calls ) {
				++$data_filter_calls;
				return $data;
			}
		);
		add_filter(
			'pre_http_request',
			function ( $pre ) use ( &$http_calls ) {
				++$http_calls;
				return $pre;
			}
		);

		WC_Tracker::send_tracking_data();

		$this->assertSame( 1, $this->count_attempt_actions( array( 'attempt' => 0 ) ) );
		$this->assertSame( 0, $data_filter_calls );
		$this->assertSame( 0, $http_calls );
		$this->assertNotFalse( get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * @testdox Public sends do not start a cycle when usage tracking consent is disabled.
	 */
	public function test_send_tracking_data_does_not_enqueue_without_consent(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );

		WC_Tracker::send_tracking_data();

		$this->assertSame( 0, $this->count_attempt_actions() );
		$this->assertFalse( get_option( 'woocommerce_tracker_last_send', false ) );
	}

	/**
	 * @testdox Delivery attempts recheck usage tracking consent before generating or posting data.
	 */
	public function test_send_tracking_data_attempt_does_no_work_without_consent(): void {
		$data_filter_calls = 0;
		$http_calls        = 0;
		update_option( 'woocommerce_allow_tracking', 'no' );
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) use ( &$data_filter_calls ) {
				++$data_filter_calls;
				return $data;
			}
		);
		add_filter(
			'pre_http_request',
			function ( $pre ) use ( &$http_calls ) {
				++$http_calls;
				return $pre;
			}
		);

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 0, $data_filter_calls );
		$this->assertSame( 0, $http_calls );
		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * @testdox Public sends preserve weekly and override throttling.
	 *
	 * @dataProvider send_throttle_data
	 * @param bool $override         Whether to override the weekly cadence.
	 * @param int  $last_send_offset Seconds since the prior cycle started.
	 */
	public function test_send_tracking_data_preserves_throttling( bool $override, int $last_send_offset ): void {
		update_option( 'woocommerce_tracker_last_send', time() - $last_send_offset );

		WC_Tracker::send_tracking_data( $override );

		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * Data for send throttling.
	 *
	 * @return array<string, array{bool, int}>
	 */
	public static function send_throttle_data(): array {
		return array(
			'weekly cadence' => array( false, DAY_IN_SECONDS ),
			'override hour'  => array( true, 30 * MINUTE_IN_SECONDS ),
		);
	}

	/**
	 * @testdox A failed initial enqueue logs a bounded failure without advancing the delivery-cycle timestamp.
	 */
	public function test_send_tracking_data_does_not_update_last_send_when_enqueue_fails(): void {
		$warning_calls = array();
		$logger        = $this->create_warning_logger( $warning_calls );
		$logger_filter = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'pre_as_enqueue_async_action', '__return_zero' );
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			WC_Tracker::send_tracking_data();
		} finally {
			remove_filter( 'pre_as_enqueue_async_action', '__return_zero' );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertSame( 0, $this->count_attempt_actions() );
		$this->assertFalse( get_option( 'woocommerce_tracker_last_send', false ) );
		$this->assertCount( 1, $warning_calls );
		$this->assertSame( 'WooCommerce tracker delivery attempt failed.', $warning_calls[0]['message'] );
		$this->assertSame(
			array(
				'source'     => 'woocommerce-tracker',
				'attempt'    => 0,
				'error_code' => 'initial_scheduling_failure',
			),
			$warning_calls[0]['context']
		);
	}

	/**
	 * @testdox Initial scheduler exceptions are contained and logged without advancing the cycle timestamp.
	 */
	public function test_send_tracking_data_contains_initial_scheduler_exception(): void {
		$sensitive_text   = 'Private initial scheduler exception details';
		$warning_calls    = array();
		$scheduler_filter = function () use ( $sensitive_text ) {
			throw new RuntimeException( $sensitive_text ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Intentionally sensitive test fixture that must not be logged.
		};
		$logger           = $this->create_warning_logger( $warning_calls );
		$logger_filter    = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'pre_as_enqueue_async_action', $scheduler_filter );
		add_filter( 'woocommerce_logging_class', $logger_filter );
		$caught_exception = null;

		try {
			WC_Tracker::send_tracking_data();
		} catch ( Throwable $exception ) {
			$caught_exception = $exception;
		} finally {
			remove_filter( 'pre_as_enqueue_async_action', $scheduler_filter );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertNull( $caught_exception, 'Initial scheduler exceptions must not escape the public tracker entry point.' );
		$this->assertFalse( get_option( 'woocommerce_tracker_last_send', false ) );
		$this->assertCount( 1, $warning_calls );
		$this->assertSame( 'initial_scheduling_failure', $warning_calls[0]['context']['error_code'] );
		$this->assertSame( 0, $warning_calls[0]['context']['attempt'] );
		$this->assertStringNotContainsString( $sensitive_text, wp_json_encode( $warning_calls ) );
	}

	/**
	 * @testdox An existing cycle is confirmed without duplicating attempt zero.
	 */
	public function test_send_tracking_data_confirms_existing_cycle_without_duplicate(): void {
		$warning_calls = array();
		$logger        = $this->create_warning_logger( $warning_calls );
		$logger_filter = function () use ( $logger ) {
			return $logger;
		};
		as_enqueue_async_action( 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 0 ), 'woocommerce-tracker', true );
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			WC_Tracker::send_tracking_data();
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertSame( 1, $this->count_attempt_actions( array( 'attempt' => 0 ) ) );
		$this->assertNotFalse( get_option( 'woocommerce_tracker_last_send', false ) );
		$this->assertCount( 0, $warning_calls, 'An existing initial action should be treated as a confirmed cycle.' );
	}

	/**
	 * @testdox Any successful HTTP status completes the cycle.
	 *
	 * @dataProvider successful_status_data
	 * @param int $status HTTP response status.
	 */
	public function test_send_tracking_data_attempt_accepts_any_2xx_status( int $status ): void {
		$this->stub_tracker_request( $status );

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * Successful response codes.
	 *
	 * @return array<string, array{int}>
	 */
	public static function successful_status_data(): array {
		return array(
			'200' => array( 200 ),
			'204' => array( 204 ),
			'299' => array( 299 ),
		);
	}

	/**
	 * @testdox Retryable transport and HTTP failures schedule the next attempt.
	 *
	 * @dataProvider retryable_response_data
	 * @param int|string $response Response code or WP_Error marker.
	 */
	public function test_send_tracking_data_attempt_retries_transient_failures( $response ): void {
		$this->stub_tracker_request( $response );

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 1, $this->count_attempt_actions( array( 'attempt' => 1 ) ) );
	}

	/**
	 * Retryable responses.
	 *
	 * @return array<string, array{int|string}>
	 */
	public static function retryable_response_data(): array {
		return array(
			'WP_Error' => array( 'wp_error' ),
			'0'        => array( 0 ),
			'408'      => array( 408 ),
			'425'      => array( 425 ),
			'429'      => array( 429 ),
			'500'      => array( 500 ),
			'503'      => array( 503 ),
			'599'      => array( 599 ),
		);
	}

	/**
	 * @testdox Terminal HTTP responses do not schedule another attempt.
	 *
	 * @dataProvider terminal_response_data
	 * @param int $status HTTP response status.
	 */
	public function test_send_tracking_data_attempt_stops_for_terminal_responses( int $status ): void {
		$this->stub_tracker_request( $status );

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * Terminal responses.
	 *
	 * @return array<string, array{int}>
	 */
	public static function terminal_response_data(): array {
		return array(
			'300' => array( 300 ),
			'302' => array( 302 ),
			'400' => array( 400 ),
			'401' => array( 401 ),
			'404' => array( 404 ),
			'422' => array( 422 ),
		);
	}

	/**
	 * @testdox Serialization failure is terminal and makes no HTTP request.
	 */
	public function test_send_tracking_data_attempt_stops_when_serialization_fails(): void {
		$http_calls = 0;
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) {
				$data['not_serializable'] = INF;
				return $data;
			}
		);
		add_filter(
			'pre_http_request',
			function ( $pre ) use ( &$http_calls ) {
				++$http_calls;
				return $pre;
			}
		);

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 0, $http_calls );
		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * @testdox Retry attempts use the exponential 15, 30, 60, and 120 minute delays.
	 *
	 * @dataProvider retry_timing_data
	 * @param int $attempt        Current zero-based attempt number.
	 * @param int $expected_delay Expected retry delay in seconds.
	 */
	public function test_send_tracking_data_attempt_uses_exponential_delay( int $attempt, int $expected_delay ): void {
		$this->stub_tracker_request( 503 );
		$started_at = time();

		WC_Tracker::send_tracking_data_attempt( $attempt );

		$scheduled_at = as_next_scheduled_action(
			'woocommerce_tracker_send_event_attempt',
			array( 'attempt' => $attempt + 1 ),
			'woocommerce-tracker'
		);
		$this->assertIsInt( $scheduled_at );
		$this->assertEqualsWithDelta( $started_at + $expected_delay, $scheduled_at, 5 );
	}

	/**
	 * Retry delays.
	 *
	 * @return array<string, array{int, int}>
	 */
	public static function retry_timing_data(): array {
		return array(
			'attempt 0' => array( 0, 15 * MINUTE_IN_SECONDS ),
			'attempt 1' => array( 1, 30 * MINUTE_IN_SECONDS ),
			'attempt 2' => array( 2, HOUR_IN_SECONDS ),
			'attempt 3' => array( 3, 2 * HOUR_IN_SECONDS ),
		);
	}

	/**
	 * @testdox The fifth failed attempt ends the cycle.
	 */
	public function test_send_tracking_data_attempt_stops_at_attempt_cap(): void {
		$this->stub_tracker_request( 503 );

		WC_Tracker::send_tracking_data_attempt( 4 );

		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * @testdox Retry scheduling delegates exact successor deduplication atomically to Action Scheduler.
	 */
	public function test_send_tracking_data_attempt_schedules_retry_as_unique(): void {
		$scheduled_as_unique = null;
		$schedule_filter     = function ( $pre, $timestamp, $hook, $args, $group, $priority, $unique ) use ( &$scheduled_as_unique ) {
			if ( 'woocommerce_tracker_send_event_attempt' === $hook ) {
				$scheduled_as_unique = $unique;
				return 123;
			}

			return $pre;
		};
		add_filter( 'pre_as_schedule_single_action', $schedule_filter, 10, 7 );
		$this->stub_tracker_request( 503 );

		WC_Tracker::send_tracking_data_attempt();

		remove_filter( 'pre_as_schedule_single_action', $schedule_filter );
		$this->assertTrue( $scheduled_as_unique );
	}

	/**
	 * @testdox A zero scheduling result confirms an existing exact successor without duplication or failure logging.
	 */
	public function test_send_tracking_data_attempt_deduplicates_exact_next_attempt(): void {
		as_schedule_single_action( time() + 900, 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 1 ), 'woocommerce-tracker', false );
		$schedule_calls  = 0;
		$warning_calls   = array();
		$schedule_filter = function ( $pre, $timestamp, $hook ) use ( &$schedule_calls ) {
			if ( 'woocommerce_tracker_send_event_attempt' === $hook ) {
				++$schedule_calls;
				return 0;
			}

			return $pre;
		};
		$logger          = $this->create_warning_logger( $warning_calls );
		$logger_filter   = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'pre_as_schedule_single_action', $schedule_filter, 10, 3 );
		add_filter( 'woocommerce_logging_class', $logger_filter );
		$this->stub_tracker_request( 503 );

		try {
			WC_Tracker::send_tracking_data_attempt();
		} finally {
			remove_filter( 'pre_as_schedule_single_action', $schedule_filter );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertSame( 1, $schedule_calls );
		$this->assertSame( 1, $this->count_attempt_actions( array( 'attempt' => 1 ) ) );
		$this->assertCount( 1, $warning_calls, 'Only the HTTP failure should be logged when an exact successor exists.' );
	}

	/**
	 * @testdox A zero scheduling result without an exact successor logs a bounded scheduling failure.
	 */
	public function test_send_tracking_data_attempt_logs_failed_retry_scheduling(): void {
		$warning_calls   = array();
		$schedule_filter = function () {
			return 0;
		};
		$logger          = $this->create_warning_logger( $warning_calls );
		$logger_filter   = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'pre_as_schedule_single_action', $schedule_filter );
		add_filter( 'woocommerce_logging_class', $logger_filter );
		$this->stub_tracker_request( 503 );

		try {
			WC_Tracker::send_tracking_data_attempt();
		} finally {
			remove_filter( 'pre_as_schedule_single_action', $schedule_filter );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertCount( 2, $warning_calls );
		$this->assertSame( 'retry_scheduling_failure', $warning_calls[1]['context']['error_code'] );
		$this->assertSame( 0, $warning_calls[1]['context']['attempt'] );
	}

	/**
	 * @testdox Scheduler exceptions are contained and logged without sensitive exception details.
	 */
	public function test_send_tracking_data_attempt_contains_retry_scheduler_exception(): void {
		$sensitive_text  = 'Private scheduler exception details';
		$warning_calls   = array();
		$schedule_filter = function () use ( $sensitive_text ) {
			throw new RuntimeException( $sensitive_text ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Intentionally sensitive test fixture that must not be logged.
		};
		$logger          = $this->create_warning_logger( $warning_calls );
		$logger_filter   = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'pre_as_schedule_single_action', $schedule_filter );
		add_filter( 'woocommerce_logging_class', $logger_filter );
		$this->stub_tracker_request( 503 );
		$caught_exception = null;

		try {
			WC_Tracker::send_tracking_data_attempt();
		} catch ( Throwable $exception ) {
			$caught_exception = $exception;
		} finally {
			remove_filter( 'pre_as_schedule_single_action', $schedule_filter );
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}

		$this->assertNull( $caught_exception, 'Scheduler exceptions must not escape the tracker worker.' );
		$this->assertCount( 2, $warning_calls );
		$this->assertSame( 'retry_scheduling_failure', $warning_calls[1]['context']['error_code'] );
		$this->assertStringNotContainsString( $sensitive_text, wp_json_encode( $warning_calls ) );
	}

	/**
	 * @testdox Retry-After delays are honored only when valid and longer than exponential backoff.
	 *
	 * @dataProvider retry_after_data
	 * @param string $retry_after   Retry-After response header.
	 * @param int    $expected_delay Expected retry delay in seconds.
	 */
	public function test_send_tracking_data_attempt_honors_retry_after( string $retry_after, int $expected_delay ): void {
		if ( 'future-date' === $retry_after ) {
			$retry_after = gmdate( 'D, d M Y H:i:s \G\M\T', time() + $expected_delay );
		} elseif ( 'past-date' === $retry_after ) {
			$retry_after = gmdate( 'D, d M Y H:i:s \G\M\T', time() - HOUR_IN_SECONDS );
		}
		$this->stub_tracker_request( 429, $retry_after );
		$started_at = time();

		WC_Tracker::send_tracking_data_attempt();

		$scheduled_at = as_next_scheduled_action(
			'woocommerce_tracker_send_event_attempt',
			array( 'attempt' => 1 ),
			'woocommerce-tracker'
		);
		$this->assertIsInt( $scheduled_at );
		$this->assertEqualsWithDelta( $started_at + $expected_delay, $scheduled_at, 5 );
	}

	/**
	 * Retry-After response headers.
	 *
	 * @return array<string, array{string, int}>
	 */
	public static function retry_after_data(): array {
		return array(
			'positive delta' => array( '1800', 30 * MINUTE_IN_SECONDS ),
			'future date'    => array( 'future-date', 40 * MINUTE_IN_SECONDS ),
			'invalid'        => array( 'later', 15 * MINUTE_IN_SECONDS ),
			'relative date'  => array( 'tomorrow', 15 * MINUTE_IN_SECONDS ),
			'zero'           => array( '0', 15 * MINUTE_IN_SECONDS ),
			'past date'      => array( 'past-date', 15 * MINUTE_IN_SECONDS ),
		);
	}

	/**
	 * @testdox Transport failure logs use a bounded category without arbitrary error details.
	 */
	public function test_send_tracking_data_attempt_sanitizes_transport_failure_log(): void {
		$sensitive_code    = 'private-code-' . str_repeat( 'x', 200 );
		$sensitive_message = 'Private transport failure details';
		$logger            = $this->createMock( WC_Logger_Interface::class );
		$logger->expects( $this->once() )
			->method( 'warning' )
			->with(
				'WooCommerce tracker delivery attempt failed.',
				$this->callback(
					function ( $context ) use ( $sensitive_code, $sensitive_message ) {
						$logged_context = wp_json_encode( $context );

						$this->assertSame( 'transport_failure', $context['error_code'] );
						$this->assertStringNotContainsString( $sensitive_code, $logged_context );
						$this->assertStringNotContainsString( $sensitive_message, $logged_context );
						return true;
					}
				)
			);
		$logger_filter = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'woocommerce_logging_class', $logger_filter );
		$this->stub_tracker_request( new WP_Error( $sensitive_code, $sensitive_message ) );

		WC_Tracker::send_tracking_data_attempt();

		remove_filter( 'woocommerce_logging_class', $logger_filter );
	}

	/**
	 * @testdox Retry-After requests beyond 24 hours end the cycle.
	 */
	public function test_send_tracking_data_attempt_stops_for_retry_after_beyond_one_day(): void {
		$this->stub_tracker_request( 429, (string) ( DAY_IN_SECONDS + 1 ) );

		WC_Tracker::send_tracking_data_attempt();

		$this->assertSame( 0, $this->count_attempt_actions() );
	}

	/**
	 * Stub the tracker HTTP response.
	 *
	 * @param int|string|WP_Error $response    Response code, error, or WP_Error marker.
	 * @param string              $retry_after Retry-After header value.
	 */
	private function stub_tracker_request( $response, string $retry_after = '' ): void {
		add_filter(
			'woocommerce_tracker_data',
			function () {
				return array( 'snapshot' => 'current' );
			}
		);
		add_filter(
			'pre_http_request',
			function () use ( $response, $retry_after ) {
				if ( 'wp_error' === $response ) {
					return new WP_Error( 'transport_failure', 'Sensitive transport details' );
				}
				if ( is_wp_error( $response ) ) {
					return $response;
				}

				return array(
					'headers'  => $retry_after ? array( 'retry-after' => $retry_after ) : array(),
					'body'     => 'Sensitive response body',
					'response' => array(
						'code'    => $response,
						'message' => 'Response message',
					),
				);
			},
			10,
			3
		);
	}

	/**
	 * Create a logger that captures warning calls.
	 *
	 * @param array<int, array{message: string, context: array<string, mixed>}> $warning_calls Captured calls.
	 * @return WC_Logger_Interface
	 */
	private function create_warning_logger( array &$warning_calls ): WC_Logger_Interface {
		$logger = $this->createMock( WC_Logger_Interface::class );
		$logger->method( 'warning' )
			->willReturnCallback(
				function ( $message, $context ) use ( &$warning_calls ) {
					$warning_calls[] = array(
						'message' => $message,
						'context' => $context,
					);
				}
			);

		return $logger;
	}

	/**
	 * Count scheduled tracker attempt actions.
	 *
	 * @param array<string, int>|null $args Exact action arguments, or null for any.
	 * @return int
	 */
	private function count_attempt_actions( ?array $args = null ): int {
		$query = array(
			'hook'     => 'woocommerce_tracker_send_event_attempt',
			'group'    => 'woocommerce-tracker',
			'status'   => array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ),
			'per_page' => -1,
		);
		if ( null !== $args ) {
			$query['args'] = $args;
		}

		return count( as_get_scheduled_actions( $query, 'ids' ) );
	}

	/**
	 * @testDox Test the features compatibility data for plugin tracking data.
	 */
	public function test_get_tracking_data_plugin_feature_compatibility() {
		$legacy_mocks = array(
			'get_plugins' => function () {
				return array(
					'plugin1' => array(
						'Name' => 'Plugin 1',
					),
					'plugin2' => array(
						'Name' => 'Plugin 2',
					),
					'plugin3' => array(
						'Name' => 'Plugin 3',
					),
				);
			},
		);
		$this->register_legacy_proxy_function_mocks( $legacy_mocks );

		update_option( 'active_plugins', array( 'plugin1', 'plugin2' ) );

		$pluginutil_mock = $this->createMock( PluginUtil::class );
		$pluginutil_mock->method( 'is_woocommerce_aware_plugin' )
			->willReturnCallback( fn ( $plugin ) => 'plugin1' === $plugin ? false : true );

		$featurescontroller_mock = $this->createMock( FeaturesController::class );
		$featurescontroller_mock
			->method( 'get_compatible_features_for_plugin' )
			->willReturnCallback(
				function ( $plugin_name ) {
					switch ( $plugin_name ) {
						case 'plugin1':
							return array();
						case 'plugin2':
							return array(
								'compatible'   => array( 'feature1' ),
								'incompatible' => array( 'feature2' ),
								'uncertain'    => array( 'feature3' ),
							);
						case 'plugin3':
							return array(
								'compatible'   => array( 'feature2' ),
								'incompatible' => array(),
								'uncertain'    => array(
									'feature1',
									'feature3',
								),
							);
					}
				}
			);

		$container = wc_get_container();
		$container->get( PluginUtil::class ); // Ensure that the class is loaded.
		$container->replace( PluginUtil::class, $pluginutil_mock );
		$container->replace( FeaturesController::class, $featurescontroller_mock );

		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertEquals(
			array(),
			$tracking_data['active_plugins']['plugin1']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible'   => array( 'feature1' ),
				'incompatible' => array( 'feature2' ),
				'uncertain'    => array( 'feature3' ),
			),
			$tracking_data['active_plugins']['plugin2']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible' => array( 'feature2' ),
				'uncertain'  => array( 'feature1', 'feature3' ),
			),
			$tracking_data['inactive_plugins']['plugin3']['feature_compatibility']
		);

		$this->reset_container_replacements();
		$container->reset_all_resolved();
	}

	/**
	 * @testDox Test orders tracking data.
	 */
	public function test_get_tracking_data_orders() {
		$status_entries         = array( OrderInternalStatus::PROCESSING, OrderInternalStatus::COMPLETED, OrderInternalStatus::REFUNDED, OrderInternalStatus::PENDING );
		$created_via_entries    = array( 'api', 'checkout', 'admin' );
		$payment_method_entries = array( WC_Gateway_Paypal::ID, 'stripe', WC_Gateway_COD::ID );

		$order_count = $this->create_tracking_orders( $status_entries, $created_via_entries, $payment_method_entries );

		$order_data = WC_Tracker::get_tracking_data()['orders'];

		foreach ( $status_entries as $status_entry ) {
			$this->assertEquals( $order_count / count( $status_entries ), $order_data[ $status_entry ] );
		}

		// Gross revenue is for wc-completed and wc-refunded status, so we calculate expected revenue per status, multiply by 2, and then multiply by 10 to account for the 10 USD per status.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 2 * 10, $order_data['gross'] );

		// Processing gross revenue covers one status, so multiply the orders per status by the fixed 10 USD total.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 1 * 10, $order_data['processing_gross'] );

		$orders_per_gateway = count( $created_via_entries ) * 3;
		foreach ( $payment_method_entries as $payment_method_entry ) {
			$gateway_key = 'gateway_' . $payment_method_entry . '_USD';
			$this->assertEquals( $orders_per_gateway, $order_data[ $gateway_key . '_count' ] );
			$this->assertEquals( $orders_per_gateway * 10, $order_data[ $gateway_key . '_total' ] );
		}

		foreach ( $created_via_entries as $created_via_entry ) {
			$this->assertEquals( ( $order_count / count( $created_via_entries ) ), $order_data['created_via'][ $created_via_entry ] );
		}
	}

	/**
	 * Persist the order matrix read by the tracker aggregate queries.
	 *
	 * @param string[] $statuses        Order statuses.
	 * @param string[] $created_via     Order origins.
	 * @param string[] $payment_methods Payment methods.
	 * @return int Number of inserted orders.
	 */
	private function create_tracking_orders( array $statuses, array $created_via, array $payment_methods ): int {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_count = 0;
			foreach ( $statuses as $status ) {
				foreach ( $created_via as $origin ) {
					foreach ( $payment_methods as $payment_method ) {
						$order = wc_create_order(
							array(
								'status'      => $status,
								'created_via' => $origin,
							)
						);
						$order->set_payment_method( $payment_method );
						$order->set_total( 10 );
						$order->save();
						++$order_count;
					}
				}
			}

			return $order_count;
		}

		$order_date = gmdate( 'Y-m-d H:i:s' );
		$orders     = array();

		foreach ( $statuses as $status ) {
			foreach ( $created_via as $origin ) {
				foreach ( $payment_methods as $payment_method ) {
					$orders[] = array(
						'status'         => $status,
						'date'           => $order_date,
						'payment_method' => $payment_method,
						'created_via'    => $origin,
						'recorded_sales' => 0,
					);
				}
			}
		}

		return $this->insert_hpos_tracking_orders( $orders );
	}

	/**
	 * Insert minimal HPOS rows consumed by tracker queries.
	 *
	 * @param array[] $orders Order persistence data.
	 * @return int Number of inserted orders.
	 */
	private function insert_hpos_tracking_orders( array $orders ): int {
		global $wpdb;

		$next_order_id = (int) $wpdb->get_var( "SELECT GREATEST(COALESCE((SELECT MAX(id) FROM {$wpdb->prefix}wc_orders), 0), COALESCE((SELECT MAX(ID) FROM {$wpdb->posts}), 0)) + 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are provided by WordPress.
		$order_rows    = array();
		$order_values  = array();
		$detail_rows   = array();
		$detail_values = array();

		foreach ( $orders as $order ) {
			$order_rows[] = '(%d, %s, %s, %s, %f, %s, %s, %s)';
			array_push( $order_values, $next_order_id, $order['status'], 'USD', 'shop_order', 10, $order['date'], $order['date'], $order['payment_method'] );

			$detail_rows[] = '(%d, %s, %s, %d)';
			array_push( $detail_values, $next_order_id, $order['created_via'], WOOCOMMERCE_VERSION, $order['recorded_sales'] );

			++$next_order_id;
		}

		$order_table    = OrdersTableDataStore::get_orders_table_name();
		$order_columns  = 'id, status, currency, type, total_amount, date_created_gmt, date_updated_gmt, payment_method';
		$detail_table   = OrdersTableDataStore::get_operational_data_table_name();
		$detail_columns = 'order_id, created_via, woocommerce_version, recorded_sales';

		$order_query         = $wpdb->prepare(
			"INSERT INTO {$order_table} ({$order_columns}) VALUES " . implode( ', ', $order_rows ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Table and columns are selected above; placeholders are generated above.
			$order_values
		);
		$order_rows_inserted = $wpdb->query( $order_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		$this->assertSame( count( $orders ), $order_rows_inserted, 'Expected every tracker order row to be inserted.' );

		$detail_query         = $wpdb->prepare(
			"INSERT INTO {$detail_table} ({$detail_columns}) VALUES " . implode( ', ', $detail_rows ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- Table and columns are selected above; placeholders are generated above.
			$detail_values
		);
		$detail_rows_inserted = $wpdb->query( $detail_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above.
		$this->assertSame( count( $orders ), $detail_rows_inserted, 'Expected operational data for every tracker order row.' );

		( new OrderCountCache() )->flush( 'shop_order', array_keys( wc_get_order_statuses() ) );

		return count( $orders );
	}

	/**
	 * @testDox Test order snapshot data.
	 */
	public function test_get_tracking_data_order_snapshot() {
		$year     = gmdate( 'Y' );
		$first_20 = array();
		$last_20  = array();

		// Populate order dates.
		for ( $i = 1; $i <= 20; $i++ ) {
			$first_20[] = sprintf( '%d-02-%02d 12:00:00', $year - 2, $i );
			$last_20[]  = sprintf( '%d-02-%02d 12:00:00', $year + 2, $i );
		}

		$this->create_tracking_snapshot_orders( array_merge( $first_20, $last_20 ) );

		$order_snapshot = WC_Tracker::get_tracking_data()['order_snapshot'];

		$this->assertCount( 20, $order_snapshot['first_20_orders'] );
		$this->assertCount( 20, $order_snapshot['last_20_orders'] );

		// Check order rank for first 20 orders.
		$counter = 1;
		foreach ( $order_snapshot['first_20_orders'] as $order_details ) {
			$this->assertEquals( $order_details['order_rank'], $counter++ );
			$this->assertEquals( $order_details['currency'], 'USD' );
			$this->assertEquals( floatval( $order_details['total_amount'] ), 10.0 );
			$this->assertEquals( $order_details['recorded_sales'], 'yes' );
			$this->assertEquals( $order_details['woocommerce_version'], WOOCOMMERCE_VERSION );
		}

		// Check order rank for last 20 orders.
		$counter = 40;
		foreach ( $order_snapshot['last_20_orders'] as $order_details ) {
			$this->assertEquals( $order_details['order_rank'], $counter-- );
			$this->assertEquals( $order_details['currency'], 'USD' );
			$this->assertEquals( floatval( $order_details['total_amount'] ), 10.00 );
			$this->assertEquals( $order_details['recorded_sales'], 'yes' );
			$this->assertEquals( $order_details['woocommerce_version'], WOOCOMMERCE_VERSION );
		}
	}

	/**
	 * Persist orders read by the first/last order snapshot queries.
	 *
	 * @param string[] $order_dates Order creation dates.
	 */
	private function create_tracking_snapshot_orders( array $order_dates ): void {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			foreach ( $order_dates as $order_date ) {
				$order = wc_create_order(
					array(
						'status' => OrderInternalStatus::COMPLETED,
					)
				);
				$order->set_date_created( $order_date );
				$order->set_total( 10 );
				$order->save();
			}
			return;
		}

		$orders = array_map(
			static fn( $order_date ) => array(
				'status'         => OrderInternalStatus::COMPLETED,
				'date'           => $order_date,
				'payment_method' => '',
				'created_via'    => 'admin',
				'recorded_sales' => 1,
			),
			$order_dates
		);
		$this->insert_hpos_tracking_orders( $orders );
	}

	/**
	 * @testDox Test enabled features tracking data.
	 */
	public function test_get_tracking_data_enabled_features() {
		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertIsArray( $tracking_data['enabled_features'] );
	}

	/**
	 * @testDox Test store_id is included in tracking data.
	 */
	public function test_get_tracking_data_store_id() {
		update_option( \WC_Install::STORE_ID_OPTION, '12345' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'store_id', $tracking_data );
		$this->assertEquals( '12345', $tracking_data['store_id'] );
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * @testDox Test woocommerce_install_admin_timestamp is included in tracking data.
	 */
	public function test_get_tracking_data_admin_install_timestamp() {
		$time = time();
		update_option( 'woocommerce_admin_install_timestamp', $time );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'admin_install_timestamp', $tracking_data['settings'] );
		$this->assertEquals( $tracking_data['settings']['admin_install_timestamp'], $time );
		delete_option( 'woocommerce_admin_install_timestamp' );
	}

	/**
	 * @testDox Test tracking data records snapshot generation time.
	 */
	public function test_get_tracking_data_snapshot_generation_time() {
		$this->assertGreaterThan( 0, WC_Tracker::get_tracking_data()['snapshot_generation_time'] );
	}

	/**
	 * @testDox Test woocommerce_allow_tracking related data is included in tracking snapshot.
	 */
	public function test_tracking_data_woocommerce_allow_tracking() {
		$current_woocommerce_allow_tracking = get_option( 'woocommerce_allow_tracking', 'no' );

		// Clear everything.
		update_option( 'woocommerce_allow_tracking', 'no' );
		delete_option( 'woocommerce_allow_tracking_last_modified' );
		delete_option( 'woocommerce_allow_tracking_first_optin' );

		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'woocommerce_allow_tracking', $tracking_data );
		$this->assertArrayHasKey( 'woocommerce_allow_tracking_last_modified', $tracking_data );
		$this->assertArrayHasKey( 'woocommerce_allow_tracking_first_optin', $tracking_data );

		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'no' );
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking_last_modified'], 'unknown' );
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking_first_optin'], 'unknown' );

		$before = time();
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'yes' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		// first_optin is recorded once on the first opt-in and must never change afterwards.
		$first_optin = (int) get_option( 'woocommerce_allow_tracking_first_optin' );

		// last_modified must be refreshed to the current time on every tracking change. Capturing the
		// time immediately before each update keeps this deterministic without waiting on the clock.
		$before = time();
		update_option( 'woocommerce_allow_tracking', 'no' );
		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'no' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertEquals( $first_optin, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		$before = time();
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertEquals( $tracking_data['woocommerce_allow_tracking'], 'yes' );
		$this->assertGreaterThanOrEqual( $before, (int) $tracking_data['woocommerce_allow_tracking_last_modified'] );
		$this->assertEquals( $first_optin, (int) $tracking_data['woocommerce_allow_tracking_first_optin'] );

		// Restore everything as it was.
		update_option( 'woocommerce_allow_tracking', $current_woocommerce_allow_tracking );
		delete_option( 'woocommerce_allow_tracking_last_modified' );
		delete_option( 'woocommerce_allow_tracking_first_optin' );
	}

	/**
	 * @testDox Test address autocomplete tracking data.
	 */
	public function test_get_address_autocomplete_info() {
		// Test when address autocomplete is disabled (default).
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );
		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertEmpty( $data['providers'] );
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test when address autocomplete is enabled but no providers registered.
		update_option( 'woocommerce_address_autocomplete_enabled', 'yes' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should be disabled if no providers are available.
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertEmpty( $data['providers'] );
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test with a single registered provider and preferred provider set.
		$this->register_mock_address_provider();
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider' );

		update_option( 'woocommerce_address_autocomplete_enabled', 'yes' );
		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'yes', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 1, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		// Should return the preferred provider we set.
		$this->assertEquals( 'mock-address-provider', $data['preferred_provider'] );

		// Clean up before testing multiple providers.
		remove_all_filters( 'woocommerce_address_providers' );

		// Test with multiple registered providers and different preferred provider.
		$this->register_multiple_mock_address_providers();
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider-two' );

		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'yes', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 2, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		$this->assertContains( 'mock-address-provider-two', $data['providers'] );
		// Should return the second provider as preferred.
		$this->assertEquals( 'mock-address-provider-two', $data['preferred_provider'] );

		// Test with invalid preferred provider (not in the list).
		update_option( 'woocommerce_address_autocomplete_provider', 'non-existent-provider' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should fall back to the first provider when the preferred provider doesn't exist.
		$this->assertEquals( 'mock-address-provider', $data['preferred_provider'] );

		// Test with multiple registered providers but feature disabled.
		$this->register_multiple_mock_address_providers();
		update_option( 'woocommerce_address_autocomplete_enabled', 'no' );
		update_option( 'woocommerce_address_autocomplete_provider', 'mock-address-provider-two' );

		$data = WC_Tracker::get_address_autocomplete_info();
		$this->assertEquals( 'no', $data['enabled'] );
		$this->assertIsArray( $data['providers'] );
		$this->assertCount( 2, $data['providers'] );
		$this->assertContains( 'mock-address-provider', $data['providers'] );
		$this->assertContains( 'mock-address-provider-two', $data['providers'] );
		// Should return the second provider as preferred.
		$this->assertEquals( '', $data['preferred_provider'] );

		// Test with invalid preferred provider (not in the list) when feature is disabled.
		update_option( 'woocommerce_address_autocomplete_provider', 'non-existent-provider' );
		$data = WC_Tracker::get_address_autocomplete_info();
		// Should not fall back to the first provider when the preferred provider doesn't exist.
		$this->assertEquals( '', $data['preferred_provider'] );

		// Clean up.
		delete_option( 'woocommerce_address_autocomplete_enabled' );
		delete_option( 'woocommerce_address_autocomplete_provider' );
		remove_all_filters( 'woocommerce_address_providers' );
		// Re-init address providers to ensure class is clean for other tests.
		wc_get_container()->get( \Automattic\WooCommerce\Internal\AddressProvider\AddressProviderController::class )->init();
	}

	/**
	 * Helper method to register a mock address provider.
	 */
	private function register_mock_address_provider() {
		// Register the provider instance.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				$providers[] = new WC_Tracker_Test_MockAddressProvider();
				return $providers;
			}
		);
	}

	/**
	 * Helper method to register multiple mock address providers.
	 */
	private function register_multiple_mock_address_providers() {
		// Register multiple provider instances with different IDs.
		add_filter(
			'woocommerce_address_providers',
			function ( $providers ) {
				$providers[] = new WC_Tracker_Test_MockAddressProvider( 'mock-address-provider', 'Mock Address Provider' );
				$providers[] = new WC_Tracker_Test_MockAddressProvider( 'mock-address-provider-two', 'Mock Address Provider Two' );
				return $providers;
			}
		);
	}
}
