<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use WC_Log_Levels;
use WC_Logger_Interface;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationStepLogger class.
 */
class NotificationStepLoggerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationStepLogger
	 */
	private $sut;

	/**
	 * The fake logger capturing calls.
	 *
	 * @var object
	 */
	private $logger;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new NotificationStepLogger();

		$this->logger = $this->create_fake_logger();
		$logger       = $this->logger;
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ) {
				return $logger;
			}
		);
	}

	/**
	 * @testdox Should write a notification step at info level to the type source with the identifying context.
	 */
	public function test_log_notification_step_writes_to_the_type_source(): void {
		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'recipients', 'resolved', array( 'recipients' => 3 ) );

		$this->assertCount( 1, $this->logger->info_calls );

		$context = $this->logger->info_calls[0]['context'];
		$this->assertSame( 'push-notifications-store-order', $context['source'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $context['identifier'] );
		$this->assertSame( 'store_order', $context['type'] );
		$this->assertSame( 42, $context['resource_id'] );
		$this->assertSame( 'recipients', $context['step'] );
		$this->assertSame( 'resolved', $context['outcome'] );
		$this->assertSame( 3, $context['recipients'] );
		$this->assertFalse( $context['remote-logging'] );
		$this->assertSame( 'Recipients: resolved', $this->logger->info_calls[0]['message'] );
	}

	/**
	 * A caller can reach a logged exception message, and the handler writes the
	 * message into the line unencoded, so a newline would let it forge a line.
	 *
	 * @testdox Should strip control characters from a logged message so it cannot add a line.
	 */
	public function test_log_failure_strips_control_characters_from_the_message(): void {
		$this->sut->log_failure(
			$this->create_order_mock( 42 ),
			'processing',
			'exception',
			'error',
			"Unknown notification type: evil\n2026-10-02T00:00:00+00:00 INFO Dispatched: accepted"
		);

		$this->assertStringNotContainsString( "\n", $this->logger->log_calls[0]['message'] );
	}

	/**
	 * A caller controls the value an exception names, so a message can hold any
	 * bytes at all.
	 *
	 * @testdox Should still write a message whose bytes are not valid UTF-8.
	 */
	public function test_log_failure_keeps_a_message_with_invalid_utf8(): void {
		$this->sut->log_failure(
			$this->create_order_mock( 42 ),
			'processing',
			'exception',
			'error',
			"Unknown notification type: \x80\nforged line"
		);

		$message = $this->logger->log_calls[0]['message'];

		$this->assertStringContainsString( 'Unknown notification type:', $message );
		$this->assertStringNotContainsString( "\n", $message );
	}

	/**
	 * @testdox Should cap a logged message rather than write whatever an exception carried.
	 */
	public function test_log_failure_caps_the_message_length(): void {
		$this->sut->log_failure( $this->create_order_mock( 42 ), 'processing', 'exception', 'error', str_repeat( 'a', 5000 ) );

		$this->assertStringEndsWith( '... (truncated)', $this->logger->log_calls[0]['message'] );
		$this->assertLessThan( 5000, strlen( $this->logger->log_calls[0]['message'] ) );
	}

	/**
	 * @testdox Should cap a context string so a caller-supplied value cannot fill the line.
	 */
	public function test_context_strings_are_capped(): void {
		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'recipients', 'resolved', array( 'note' => str_repeat( 'b', 1000 ) ) );

		$this->assertStringEndsWith( '... (truncated)', $this->logger->info_calls[0]['context']['note'] );
	}

	/**
	 * @testdox Should cap a context array and say how many entries it left out.
	 */
	public function test_context_arrays_are_capped_and_count_what_was_omitted(): void {
		$this->sut->log_notification_step(
			$this->create_order_mock( 42 ),
			'recipients',
			'resolved',
			array( 'token_ids' => range( 1, 450 ) )
		);

		$context = $this->logger->info_calls[0]['context'];

		$this->assertCount( NotificationStepLogger::MAX_ARRAY_ITEMS, $context['token_ids'] );
		$this->assertSame( 350, $context['token_ids_omitted'] );
	}

	/**
	 * @testdox Should cap each list of a grouped map and count what each one left out.
	 */
	public function test_grouped_context_arrays_are_capped_per_group(): void {
		$this->sut->log_suppressed_step(
			$this->create_order_mock( 42 ),
			'token_excluded',
			'various',
			array(
				'excluded_tokens' => array(
					'notifications_off' => range( 1, 250 ),
					'no_account'        => range( 1, 5 ),
				),
			)
		);

		$context = $this->logger->info_calls[0]['context'];

		$this->assertCount( NotificationStepLogger::MAX_ARRAY_ITEMS, $context['excluded_tokens']['notifications_off'] );
		$this->assertCount( 5, $context['excluded_tokens']['no_account'] );
		$this->assertSame( array( 'notifications_off' => 150 ), $context['excluded_tokens_omitted'] );
	}

	/**
	 * @testdox Should write an excluded-token step to the store-wide suppressed source.
	 */
	public function test_log_suppressed_step_writes_to_the_suppressed_source(): void {
		$this->sut->log_suppressed_step(
			$this->create_order_mock( 42 ),
			'token_excluded',
			'notifications_off',
			array( 'excluded_tokens' => array( 'notifications_off' => array( 4412 ) ) )
		);

		$this->assertCount( 1, $this->logger->info_calls );

		$context = $this->logger->info_calls[0]['context'];
		$this->assertSame( NotificationStepLogger::SUPPRESSED_SOURCE, $context['source'] );
		$this->assertSame( array( 'notifications_off' => array( 4412 ) ), $context['excluded_tokens'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $context['identifier'] );
		$this->assertSame( 'token_excluded', $context['step'] );
		$this->assertSame( 'notifications_off', $context['outcome'] );
	}

	/**
	 * @testdox Should carry the stock event type in the identifier so same-product events stay distinct.
	 */
	public function test_stock_identifier_includes_the_event_type(): void {
		$notification = $this->getMockBuilder( StockNotification::class )
			->setConstructorArgs( array( 99, StockNotification::EVENT_LOW_STOCK ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();

		$this->sut->log_notification_step( $notification, 'triggered', 'ok' );

		$this->assertSame( 'push-notifications-store-stock', $this->logger->info_calls[0]['context']['source'] );
		$this->assertSame( $notification->get_identifier(), $this->logger->info_calls[0]['context']['identifier'] );
	}

	/**
	 * @testdox Should not let a caller's context overwrite the identifying fields.
	 */
	public function test_caller_context_cannot_overwrite_identifying_fields(): void {
		$this->sut->log_notification_step(
			$this->create_order_mock( 42 ),
			'triggered',
			'ok',
			array(
				'identifier' => 'forged',
				'source'     => 'elsewhere',
			)
		);

		$context = $this->logger->info_calls[0]['context'];
		$this->assertSame( 'push-notifications-store-order', $context['source'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $context['identifier'] );
	}

	/**
	 * @testdox Should write nothing when the filter returns false.
	 */
	public function test_writes_nothing_when_the_filter_disables_logging(): void {
		add_filter( 'woocommerce_push_notification_step_logging_enabled', '__return_false' );

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );

		$this->assertCount( 0, $this->logger->info_calls );
		$this->assertFalse( $this->sut->is_active() );
	}

	/**
	 * @testdox Should swallow a throwing sanitize_title callback rather than let it reach the send.
	 */
	public function test_swallows_a_throwing_sanitize_title_callback(): void {
		add_filter(
			'sanitize_title',
			static function () {
				throw new \RuntimeException( 'callback exploded' );
			}
		);

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );

		$this->assertCount( 0, $this->logger->info_calls );
	}

	/**
	 * @testdox Should treat a filter callback that throws as logging turned off.
	 */
	public function test_a_throwing_filter_turns_logging_off(): void {
		add_filter(
			'woocommerce_push_notification_step_logging_enabled',
			static function () {
				throw new \RuntimeException( 'callback exploded' );
			}
		);

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );

		$this->assertFalse( $this->sut->is_active() );
		$this->assertCount( 0, $this->logger->info_calls );
	}

	/**
	 * @testdox Should decide whether logging is active once per request.
	 */
	public function test_activation_is_decided_once(): void {
		$applications = 0;
		add_filter(
			'woocommerce_push_notification_step_logging_enabled',
			static function ( $enabled ) use ( &$applications ) {
				++$applications;
				return $enabled;
			}
		);

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );
		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'queued', 'ok' );

		$this->assertSame( 1, $applications );
		$this->assertCount( 2, $this->logger->info_calls );
	}

	/**
	 * @testdox Should swallow a logger failure so the send is unaffected.
	 */
	public function test_swallows_logger_failures(): void {
		add_filter(
			'woocommerce_logging_class',
			static function () {
				return new class() implements WC_Logger_Interface {
					// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter
					public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
						return true;
					}
					public function log( $level, $message, $context = array() ) {}
					public function emergency( $message, $context = array() ) {}
					public function alert( $message, $context = array() ) {}
					public function critical( $message, $context = array() ) {}
					public function error( $message, $context = array() ) {}
					public function warning( $message, $context = array() ) {}
					public function notice( $message, $context = array() ) {}
					public function debug( $message, $context = array() ) {}
					public function info( $message, $context = array() ) {
						throw new \RuntimeException( 'disk full' );
					}
					// phpcs:enable
				};
			},
			20
		);

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );

		$this->assertTrue( true, 'No exception reached the caller.' );
	}

	/**
	 * @testdox Should record a failure as an error line under the module source and as a step line under the notification source.
	 */
	public function test_log_failure_writes_an_error_line_and_a_step_line(): void {
		$this->sut->log_failure(
			$this->create_order_mock( 42 ),
			'dispatched',
			'request_failed',
			'error',
			'Push notification request failed.',
			array( 'http_status' => 500 )
		);

		$this->assertCount( 1, $this->logger->log_calls );
		$this->assertCount( 1, $this->logger->info_calls );

		$error = $this->logger->log_calls[0];
		$this->assertSame( 'error', $error['level'] );
		$this->assertSame( 'Push notification request failed.', $error['message'] );
		$this->assertSame( 'push_notifications', $error['context']['source'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $error['context']['identifier'] );
		$this->assertSame( 'store_order', $error['context']['type'] );
		$this->assertSame( 42, $error['context']['resource_id'] );
		$this->assertSame( 'dispatched', $error['context']['step'] );
		$this->assertSame( 'request_failed', $error['context']['outcome'] );
		$this->assertSame( 500, $error['context']['http_status'] );
		$this->assertFalse( $error['context']['remote-logging'] );

		$step = $this->logger->info_calls[0];
		$this->assertSame( 'Dispatched: request failed', $step['message'] );
		$this->assertSame( 'push-notifications-store-order', $step['context']['source'] );
		$this->assertSame( 500, $step['context']['http_status'] );
	}

	/**
	 * @testdox Should write the failure's error line at the level the caller asked for.
	 */
	public function test_log_failure_uses_the_given_level(): void {
		$this->sut->log_failure( $this->create_order_mock( 42 ), 'loopback_requested', 'request_failed', 'warning', 'Loopback request failed.' );

		$this->assertSame( 'warning', $this->logger->log_calls[0]['level'] );
	}

	/**
	 * @testdox Should still record the error line when identifying the notification fails.
	 */
	public function test_log_failure_records_the_error_when_identification_fails(): void {
		$notification = $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( 42 ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta', 'get_identifier' ) )
			->getMock();
		$notification->method( 'get_identifier' )->willThrowException( new \RuntimeException( 'getter exploded' ) );

		$this->sut->log_failure( $notification, 'dispatched', 'request_failed', 'error', 'Push notification request failed.' );

		$this->assertCount( 0, $this->logger->info_calls );
		$this->assertCount( 1, $this->logger->log_calls );

		$error = $this->logger->log_calls[0];
		$this->assertSame( 'error', $error['level'] );
		$this->assertSame( 'Push notification request failed.', $error['message'] );
		$this->assertSame( 'push_notifications', $error['context']['source'] );
		$this->assertArrayNotHasKey( 'identifier', $error['context'] );
	}

	/**
	 * @testdox Should record an unattributed failure under the module source only, with no step line.
	 */
	public function test_log_unattributed_failure_writes_no_step_line(): void {
		$this->sut->log_unattributed_failure( 'loopback_started', 'auth_failed', 'warning', 'Loopback request refused.', array( 'reason' => 'token_invalid' ) );

		$this->assertCount( 0, $this->logger->info_calls );
		$this->assertCount( 1, $this->logger->log_calls );

		$error = $this->logger->log_calls[0];
		$this->assertSame( 'warning', $error['level'] );
		$this->assertSame( 'push_notifications', $error['context']['source'] );
		$this->assertSame( 'loopback_started', $error['context']['step'] );
		$this->assertSame( 'auth_failed', $error['context']['outcome'] );
		$this->assertSame( 'token_invalid', $error['context']['reason'] );
		$this->assertArrayNotHasKey( 'identifier', $error['context'] );
	}

	/**
	 * @testdox Should keep writing the error line when the filter turns step logging off.
	 */
	public function test_the_filter_does_not_suppress_failure_lines(): void {
		add_filter( 'woocommerce_push_notification_step_logging_enabled', '__return_false' );

		$this->sut->log_failure( $this->create_order_mock( 42 ), 'dispatched', 'request_failed', 'error', 'Push notification request failed.' );
		$this->sut->log_unattributed_failure( 'loopback_started', 'auth_failed', 'warning', 'Loopback request refused.' );

		$this->assertCount( 0, $this->logger->info_calls );
		$this->assertCount( 2, $this->logger->log_calls );
	}

	/**
	 * @testdox Should swallow a logger failure while writing an error so the send is unaffected.
	 */
	public function test_swallows_logger_failures_when_writing_an_error(): void {
		add_filter(
			'woocommerce_logging_class',
			static function () {
				return new class() implements WC_Logger_Interface {
					// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter
					public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
						return true;
					}
					public function log( $level, $message, $context = array() ) {
						throw new \RuntimeException( 'disk full' );
					}
					public function emergency( $message, $context = array() ) {}
					public function alert( $message, $context = array() ) {}
					public function critical( $message, $context = array() ) {}
					public function error( $message, $context = array() ) {}
					public function warning( $message, $context = array() ) {}
					public function notice( $message, $context = array() ) {}
					public function debug( $message, $context = array() ) {}
					public function info( $message, $context = array() ) {}
					// phpcs:enable
				};
			},
			20
		);

		$this->sut->log_unattributed_failure( 'loopback_started', 'auth_failed', 'warning', 'Loopback request refused.' );

		$this->assertTrue( true, 'No exception reached the caller.' );
	}

	/**
	 * Creates a mock NewOrderNotification that avoids database calls.
	 *
	 * @param int $resource_id The resource ID.
	 * @return NewOrderNotification
	 */
	private function create_order_mock( int $resource_id ): NewOrderNotification {
		return $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( $resource_id ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();
	}

	/**
	 * Creates a fake logger that records info and log calls.
	 *
	 * @return object
	 */
	private function create_fake_logger(): object {
		return new class() implements WC_Logger_Interface {
			// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter
			public array $info_calls = array();
			public array $log_calls  = array();

			public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
				return true;
			}
			public function log( $level, $message, $context = array() ) {
				$this->log_calls[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
			public function emergency( $message, $context = array() ) {}
			public function alert( $message, $context = array() ) {}
			public function critical( $message, $context = array() ) {}
			public function error( $message, $context = array() ) {}
			public function warning( $message, $context = array() ) {}
			public function notice( $message, $context = array() ) {}
			public function debug( $message, $context = array() ) {}
			public function info( $message, $context = array() ) {
				$this->info_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
			// phpcs:enable
		};
	}
}
