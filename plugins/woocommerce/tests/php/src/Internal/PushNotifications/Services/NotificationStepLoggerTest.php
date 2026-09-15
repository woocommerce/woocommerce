<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
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
	 * The mocked push tokens data store.
	 *
	 * @var PushTokensDataStore|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $data_store;

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

		$this->data_store = $this->createMock( PushTokensDataStore::class );
		$this->data_store->method( 'has_ever_had_tokens' )->willReturn( true );

		$this->sut = new NotificationStepLogger();
		$this->sut->init( $this->data_store );

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
		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'cleared_to_send', 'ok', array( 'recipients' => 3 ) );

		$this->assertCount( 1, $this->logger->info_calls );

		$context = $this->logger->info_calls[0]['context'];
		$this->assertSame( 'push-notifications-store-order', $context['source'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $context['identifier'] );
		$this->assertSame( 'store_order', $context['type'] );
		$this->assertSame( 42, $context['resource_id'] );
		$this->assertSame( 'cleared_to_send', $context['step'] );
		$this->assertSame( 'ok', $context['outcome'] );
		$this->assertSame( 3, $context['recipients'] );
		$this->assertFalse( $context['remote-logging'] );
		$this->assertSame( 'Cleared to send: ok', $this->logger->info_calls[0]['message'] );
	}

	/**
	 * @testdox Should write a token step to that token's source with the token and user IDs.
	 */
	public function test_log_token_step_writes_to_the_token_source(): void {
		$this->sut->log_token_step( $this->create_order_mock( 42 ), 4412, 7, 'held_back', 'type_disabled' );

		$this->assertCount( 1, $this->logger->info_calls );

		$context = $this->logger->info_calls[0]['context'];
		$this->assertSame( 'push-token-4412', $context['source'] );
		$this->assertSame( 4412, $context['token_id'] );
		$this->assertSame( 7, $context['user_id'] );
		$this->assertSame( get_current_blog_id() . '_store_order_42', $context['identifier'] );
		$this->assertSame( 'held_back', $context['step'] );
		$this->assertSame( 'type_disabled', $context['outcome'] );
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
	 * @testdox Should write nothing when the kill switch filter returns false.
	 */
	public function test_writes_nothing_when_the_filter_disables_logging(): void {
		add_filter( 'woocommerce_push_notification_step_logging_enabled', '__return_false' );

		$this->sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );

		$this->assertCount( 0, $this->logger->info_calls );
		$this->assertFalse( $this->sut->is_active() );
	}

	/**
	 * @testdox Should write nothing on a store that has never registered a token.
	 */
	public function test_writes_nothing_when_the_store_has_never_had_tokens(): void {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'has_ever_had_tokens' )->willReturn( false );
		$sut = new NotificationStepLogger();
		$sut->init( $data_store );

		$sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );
		$sut->log_token_step( $this->create_order_mock( 42 ), 4412, 7, 'held_back', 'type_disabled' );

		$this->assertCount( 0, $this->logger->info_calls );
	}

	/**
	 * @testdox Should decide whether logging is active once per request.
	 */
	public function test_activation_is_decided_once(): void {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->expects( $this->once() )->method( 'has_ever_had_tokens' )->willReturn( true );
		$sut = new NotificationStepLogger();
		$sut->init( $data_store );

		$sut->log_notification_step( $this->create_order_mock( 42 ), 'triggered', 'ok' );
		$sut->log_notification_step( $this->create_order_mock( 42 ), 'queued', 'ok' );

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
	 * Creates a fake logger that records info calls.
	 *
	 * @return object
	 */
	private function create_fake_logger(): object {
		return new class() implements WC_Logger_Interface {
			// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UnusedFunctionParameter
			public array $info_calls = array();

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
				$this->info_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
			// phpcs:enable
		};
	}
}
