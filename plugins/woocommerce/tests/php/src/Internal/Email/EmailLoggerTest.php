<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailLogger;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the EmailLogger class.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\EmailLogger
 */
class EmailLoggerTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var EmailLogger
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		$this->sut = new EmailLogger();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_email_log_enabled' );
		remove_all_filters( 'woocommerce_email_log_context' );
		remove_all_filters( 'woocommerce_email_log_add_order_note' );
		remove_all_filters( 'woocommerce_mail_callback' );
		remove_all_actions( 'woocommerce_email_disabled' );
		remove_all_actions( 'woocommerce_email_skipped' );
		remove_action( 'woocommerce_email_sent', array( $this->sut, 'handle_woocommerce_email_sent' ) );
		remove_action( 'wp_mail_failed', array( $this->sut, 'capture_mail_error' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Register method adds hooks for woocommerce_email_sent, wp_mail_failed, woocommerce_email_disabled, and woocommerce_email_skipped.
	 */
	public function test_register_adds_hook(): void {
		$this->sut->register();

		$this->assertSame(
			10,
			has_action( 'woocommerce_email_sent', array( $this->sut, 'handle_woocommerce_email_sent' ) ),
			'Expected hook to be registered at priority 10 for woocommerce_email_sent'
		);
		$this->assertSame(
			10,
			has_action( 'wp_mail_failed', array( $this->sut, 'capture_mail_error' ) ),
			'Expected hook to be registered at priority 10 for wp_mail_failed'
		);
		$this->assertSame(
			10,
			has_action( 'woocommerce_email_disabled', array( $this->sut, 'handle_woocommerce_email_disabled' ) ),
			'Expected hook to be registered at priority 10 for woocommerce_email_disabled'
		);
		$this->assertSame(
			10,
			has_action( 'woocommerce_email_skipped', array( $this->sut, 'handle_woocommerce_email_skipped' ) ),
			'Expected hook to be registered at priority 10 for woocommerce_email_skipped'
		);
	}

	/**
	 * @testdox Logs an info entry when email is sent successfully.
	 */
	public function test_logs_info_on_success(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );

		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		$this->assertLogged( 'info', 'customer_processing_order' );
	}

	/**
	 * @testdox Logs a warning entry when email fails to send.
	 */
	public function test_logs_warning_on_failure(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );

		$this->sut->handle_woocommerce_email_sent( false, 'customer_processing_order', $email );

		$this->assertLogged( 'warning', 'customer_processing_order' );
	}

	/**
	 * @testdox Log context contains email_type, status, and recipient.
	 */
	public function test_log_context_contains_required_fields(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );

		$this->sut->handle_woocommerce_email_sent( true, 'new_order', $email );

		$this->assertLogged(
			'info',
			'new_order',
			array(
				'source'     => 'transactional-emails',
				'email_type' => 'new_order',
				'status'     => 'sent',
			)
		);
	}

	/**
	 * @testdox Status is "failed" when email send was unsuccessful.
	 */
	public function test_status_is_failed_on_unsuccessful_send(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );

		$this->sut->handle_woocommerce_email_sent( false, 'customer_processing_order', $email );

		$this->assertLogged( 'warning', 'customer_processing_order', array( 'status' => 'failed' ) );
	}

	/**
	 * @testdox Recipient is logged as the WordPress username for a registered user.
	 */
	public function test_recipient_is_username_for_registered_user(): void {
		$user  = self::factory()->user->create_and_get( array( 'user_email' => 'registered@example.com' ) );
		$email = $this->create_mock_email( 'customer_processing_order', 'registered@example.com' );

		try {
			$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

			$context = $this->captured_logs[0]['context'];

			$this->assertArrayHasKey( 'recipient', $context );
			$this->assertSame( $user->user_login, $context['recipient'], 'Recipient should be the WordPress username for a registered user' );
			$this->assertStringNotContainsString( 'registered@example.com', $context['recipient'], 'Raw email address should not appear in the log context' );
		} finally {
			wp_delete_user( $user->ID );
		}
	}

	/**
	 * @testdox Recipient is logged as "guest" for an email address not linked to any user account.
	 */
	public function test_recipient_is_guest_for_unregistered_email(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'guest@example.com' );

		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		$context = $this->captured_logs[0]['context'];

		$this->assertSame( 'guest', $context['recipient'], 'Recipient should be "guest" when the email is not linked to a user account' );
	}

	/**
	 * @testdox Empty recipient is logged as "guest".
	 */
	public function test_empty_recipient_is_guest(): void {
		$email = $this->create_mock_email( 'new_order', '' );

		$this->sut->handle_woocommerce_email_sent( true, 'new_order', $email );

		$context = $this->captured_logs[0]['context'];

		$this->assertSame( 'guest', $context['recipient'], 'Empty recipient should yield "guest"' );
	}

	/**
	 * @testdox Failure message includes the error reason captured from wp_mail_failed.
	 */
	public function test_failure_message_includes_error_reason(): void {
		$error = new \WP_Error( 'wp_mail_failed', 'SMTP connect() failed' );
		$this->sut->capture_mail_error( $error );

		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );
		$this->sut->handle_woocommerce_email_sent( false, 'new_order', $email );

		$this->assertLogged( 'warning', 'SMTP connect() failed' );
	}

	/**
	 * @testdox Failure message redacts email addresses embedded in the captured wp_mail_failed reason.
	 */
	public function test_failure_message_redacts_email_addresses_in_reason(): void {
		$error = new \WP_Error(
			'wp_mail_failed',
			'SMTP Error: Could not send to customer@example.com (rejected by server.example.org).'
		);
		$this->sut->capture_mail_error( $error );

		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );
		$this->sut->handle_woocommerce_email_sent( false, 'new_order', $email );

		$log = $this->captured_logs[0];
		$this->assertStringNotContainsString( 'customer@example.com', $log['message'], 'Raw recipient address must not appear in the logged message.' );
		$this->assertStringContainsString( 'server.example.org', $log['message'], 'Domain-only host names should be left intact (only address-shaped tokens are redacted).' );
		$this->assertStringContainsString( '[redacted_email]', $log['message'], 'Redacted addresses should be replaced with the [redacted_email] marker.' );
	}

	/**
	 * @testdox Success message does not include an error reason.
	 */
	public function test_success_message_has_no_error_reason(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );
		$this->sut->handle_woocommerce_email_sent( true, 'new_order', $email );

		$log = $this->captured_logs[0];

		$this->assertStringContainsString( 'sent', $log['message'] );
		$this->assertStringNotContainsString( 'failed', $log['message'] );
	}

	/**
	 * @testdox Object type is normalized to a stable short identifier for WC_Order.
	 */
	public function test_object_type_normalized_for_order(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );

		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		$this->assertLogged(
			'info',
			'customer_processing_order',
			array( 'order' => 42 )
		);
	}

	/**
	 * @testdox Object type is normalized to a stable short identifier for WC_Product.
	 */
	public function test_object_type_normalized_for_product(): void {
		$product = $this->createMock( \WC_Product::class );
		$product->method( 'get_id' )->willReturn( 10 );
		$email = $this->create_mock_email( 'some_product_email', 'customer@example.com', $product );

		$this->sut->handle_woocommerce_email_sent( true, 'some_product_email', $email );

		$this->assertLogged( 'info', 'some_product_email', array( 'product' => 10 ) );
	}

	/**
	 * @testdox Object type is normalized to a stable short identifier for WP_User.
	 */
	public function test_object_type_normalized_for_user(): void {
		$user     = new \WP_User();
		$user->ID = 5;
		$email    = $this->create_mock_email( 'customer_new_account', 'customer@example.com', $user );

		$this->sut->handle_woocommerce_email_sent( true, 'customer_new_account', $email );

		$this->assertLogged(
			'info',
			'customer_new_account',
			array( 'user' => 5 )
		);
	}

	/**
	 * @testdox Object with a get_id() requiring parameters falls back to the ID property.
	 */
	public function test_object_with_required_get_id_parameters_falls_back_to_id_property(): void {
		$wc_object     = new class() {
			/** @var int Mirrors WP_Post::$ID. */
			public int $ID = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Mirrors WP_Post::$ID.

			/**
			 * get_id with a required parameter, which the logger guard should refuse to call.
			 *
			 * @param int $context Required parameter.
			 * @return int
			 */
			public function get_id( int $context ): int {
				return $context;
			}
		};
		$wc_object->ID = 7;
		$class_name    = get_class( $wc_object );
		$email         = $this->create_mock_email( 'custom_email', 'customer@example.com', $wc_object );

		$this->sut->handle_woocommerce_email_sent( true, 'custom_email', $email );

		$context = $this->captured_logs[0]['context'];

		$this->assertArrayHasKey( $class_name, $context );
		$this->assertSame( 7, $context[ $class_name ] );
	}

	/**
	 * @testdox Object whose get_id() throws does not break logging.
	 */
	public function test_object_with_throwing_get_id_does_not_break_logging(): void {
		$wc_object     = new class() {
			/** @var int Mirrors WP_Post::$ID. */
			public int $ID = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Mirrors WP_Post::$ID.

			/**
			 * get_id that always throws to simulate a misbehaving extension object.
			 *
			 * @return never
			 * @throws \RuntimeException Always.
			 */
			public function get_id(): never {
				throw new \RuntimeException( 'broken get_id' );
			}
		};
		$wc_object->ID = 11;
		$class_name    = get_class( $wc_object );
		$email         = $this->create_mock_email( 'custom_email', 'customer@example.com', $wc_object );

		$this->sut->handle_woocommerce_email_sent( true, 'custom_email', $email );

		$context = $this->captured_logs[0]['context'];

		$this->assertArrayHasKey( $class_name, $context );
		$this->assertSame( 11, $context[ $class_name ] );
	}

	/**
	 * @testdox Object context is omitted when the email has no related object.
	 */
	public function test_object_context_omitted_when_no_object(): void {
		$email = $this->create_mock_email( 'customer_new_account', 'customer@example.com', false );

		$this->sut->handle_woocommerce_email_sent( true, 'customer_new_account', $email );

		$context = $this->captured_logs[0]['context'];

		$this->assertArrayNotHasKey( 'order', $context, 'Context should not contain order key when no object is set' );
		$this->assertArrayNotHasKey( 'product', $context, 'Context should not contain product key when no object is set' );
		$this->assertArrayNotHasKey( 'user', $context, 'Context should not contain user key when no object is set' );
	}

	/**
	 * @testdox woocommerce_email_log_enabled filter can disable logging entirely.
	 */
	public function test_log_enabled_filter_can_disable_logging(): void {
		add_filter( 'woocommerce_email_log_enabled', '__return_false' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );
		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		$this->assertEmpty( $this->captured_logs, 'No log entry should be written when the enabled filter returns false' );
	}

	/**
	 * @testdox woocommerce_email_log_context filter can modify context before logging.
	 */
	public function test_log_context_filter_can_modify_context(): void {
		add_filter(
			'woocommerce_email_log_context',
			function ( array $context ) {
				$context['custom_key'] = 'custom_value';
				return $context;
			}
		);

		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );
		$this->sut->handle_woocommerce_email_sent( true, 'new_order', $email );

		$this->assertLogged( 'info', 'new_order', array( 'custom_key' => 'custom_value' ) );
	}

	/**
	 * @testdox An order note is added when an email is sent successfully for an order.
	 */
	public function test_order_note_added_on_successful_send_for_order(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->once() )
			->method( 'add_order_note' )
			->with(
				$this->stringContains( 'sent' ),
				0,
				false,
				$this->callback( fn( $meta ) => isset( $meta['note_group'] ) && OrderNoteGroup::EMAIL_NOTIFICATION === $meta['note_group'] )
			);

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );
	}

	/**
	 * @testdox An order note is added when an email fails to send for an order (no error reason).
	 */
	public function test_order_note_added_on_failed_send_for_order(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->once() )
			->method( 'add_order_note' )
			->with(
				$this->logicalAnd(
					$this->stringContains( 'failed to send.' ),
					$this->logicalNot( $this->stringContains( 'failed to send:' ) )
				),
				0,
				false,
				$this->callback( fn( $meta ) => isset( $meta['note_group'] ) && OrderNoteGroup::EMAIL_NOTIFICATION === $meta['note_group'] )
			);

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( false, 'customer_processing_order', $email );
	}

	/**
	 * @testdox The order note for a failed send includes the error reason in colon-form.
	 */
	public function test_order_note_failure_includes_error_reason(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->once() )
			->method( 'add_order_note' )
			->with(
				$this->logicalAnd(
					$this->stringContains( 'failed to send:' ),
					$this->stringContains( 'SMTP connect() failed' )
				)
			);

		$error = new \WP_Error( 'wp_mail_failed', 'SMTP connect() failed' );
		$this->sut->capture_mail_error( $error );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( false, 'customer_processing_order', $email );
	}

	/**
	 * @testdox The order note redacts email addresses embedded in the failure reason.
	 */
	public function test_order_note_failure_redacts_email_addresses_in_reason(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->once() )
			->method( 'add_order_note' )
			->with(
				$this->logicalAnd(
					$this->stringContains( '[redacted_email]' ),
					$this->logicalNot( $this->stringContains( 'customer@example.com' ) )
				)
			);

		$error = new \WP_Error(
			'wp_mail_failed',
			'SMTP Error: Could not send to customer@example.com (rejected by server.example.org).'
		);
		$this->sut->capture_mail_error( $error );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( false, 'customer_processing_order', $email );
	}

	/**
	 * @testdox No order note is added when the email is not associated with an order.
	 */
	public function test_no_order_note_for_non_order_object(): void {
		$product = $this->createMock( \WC_Product::class );
		$product->method( 'get_id' )->willReturn( 10 );

		$email = $this->create_mock_email( 'some_product_email', 'admin@example.com', $product );

		// Should complete without throwing – product objects do not get order notes.
		$this->sut->handle_woocommerce_email_sent( true, 'some_product_email', $email );

		$this->assertLogged( 'info', 'some_product_email' );
	}

	/**
	 * @testdox No order note is added when the order object has not been read from the datastore (e.g. a preview dummy).
	 */
	public function test_no_order_note_for_unloaded_order_object(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 12345 );
		$order->method( 'get_object_read' )->willReturn( false );
		$order->expects( $this->never() )->method( 'add_order_note' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		// Logger entry should still be written even though no note is added.
		$this->assertLogged( 'info', 'customer_processing_order' );
	}

	/**
	 * @testdox No order note is added when logging is disabled by the filter.
	 */
	public function test_no_order_note_when_logging_disabled_by_filter(): void {
		add_filter( 'woocommerce_email_log_enabled', '__return_false' );

		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->never() )->method( 'add_order_note' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );
	}

	/**
	 * @testdox woocommerce_email_log_add_order_note filter can suppress the order note independently of logging.
	 */
	public function test_order_note_suppressed_by_add_order_note_filter(): void {
		add_filter( 'woocommerce_email_log_add_order_note', '__return_false' );

		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 42 );
		$order->method( 'get_object_read' )->willReturn( true );
		$order->expects( $this->never() )->method( 'add_order_note' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );
		$this->sut->handle_woocommerce_email_sent( true, 'customer_processing_order', $email );

		// Logger entry should still be written even though the note is suppressed.
		$this->assertLogged( 'info', 'customer_processing_order' );
	}

	/**
	 * @testdox Logs a notice entry when email is disabled.
	 */
	public function test_logs_notice_when_email_is_disabled(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );

		$this->sut->handle_woocommerce_email_disabled( 'customer_processing_order', $email );

		$this->assertLogged( 'notice', 'customer_processing_order' );
	}

	/**
	 * @testdox Disabled log context contains status "disabled".
	 */
	public function test_disabled_log_context_contains_disabled_status(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );

		$this->sut->handle_woocommerce_email_disabled( 'new_order', $email );

		$this->assertLogged(
			'notice',
			'new_order',
			array(
				'source'     => 'transactional-emails',
				'email_type' => 'new_order',
				'status'     => 'disabled',
			)
		);
	}

	/**
	 * @testdox Disabled log message contains "disabled".
	 */
	public function test_disabled_log_message_contains_disabled(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );

		$this->sut->handle_woocommerce_email_disabled( 'new_order', $email );

		$this->assertLogged( 'notice', 'disabled' );
	}

	/**
	 * @testdox woocommerce_email_log_enabled filter suppresses disabled log entry.
	 */
	public function test_log_enabled_filter_suppresses_disabled_entry(): void {
		add_filter( 'woocommerce_email_log_enabled', '__return_false' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );
		$this->sut->handle_woocommerce_email_disabled( 'customer_processing_order', $email );

		$this->assertEmpty( $this->captured_logs, 'No log entry should be written when the enabled filter returns false' );
	}

	/**
	 * @testdox Logs a notice entry when email is skipped.
	 */
	public function test_logs_notice_when_email_is_skipped(): void {
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );

		$this->sut->handle_woocommerce_email_skipped( \WC_Email::SKIP_REASON_NO_RECIPIENT, 'customer_processing_order', $email );

		$this->assertLogged( 'notice', 'customer_processing_order' );
	}

	/**
	 * @testdox Skipped log context contains status "skipped" and the skip reason.
	 */
	public function test_skipped_log_context_contains_skipped_status_and_reason(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );

		$this->sut->handle_woocommerce_email_skipped( \WC_Email::SKIP_REASON_NO_RECIPIENT, 'new_order', $email );

		$this->assertLogged(
			'notice',
			'new_order',
			array(
				'source'     => 'transactional-emails',
				'email_type' => 'new_order',
				'status'     => 'skipped',
				'reason'     => \WC_Email::SKIP_REASON_NO_RECIPIENT,
			)
		);
	}

	/**
	 * @testdox Skipped log message contains the skip reason.
	 */
	public function test_skipped_log_message_contains_reason(): void {
		$email = $this->create_mock_email( 'new_order', 'admin@example.com' );

		$this->sut->handle_woocommerce_email_skipped( \WC_Email::SKIP_REASON_NO_RECIPIENT, 'new_order', $email );

		$this->assertLogged( 'notice', \WC_Email::SKIP_REASON_NO_RECIPIENT );
	}

	/**
	 * @testdox woocommerce_email_log_enabled filter suppresses skipped log entry.
	 */
	public function test_log_enabled_filter_suppresses_skipped_entry(): void {
		add_filter( 'woocommerce_email_log_enabled', '__return_false' );

		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com' );
		$this->sut->handle_woocommerce_email_skipped( \WC_Email::SKIP_REASON_NO_RECIPIENT, 'customer_processing_order', $email );

		$this->assertEmpty( $this->captured_logs, 'No log entry should be written when the enabled filter returns false' );
	}

	/**
	 * @testdox Disabled log includes object context for WC_Order.
	 */
	public function test_disabled_log_includes_order_context(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 99 );
		$email = $this->create_mock_email( 'customer_processing_order', 'customer@example.com', $order );

		$this->sut->handle_woocommerce_email_disabled( 'customer_processing_order', $email );

		$this->assertLogged(
			'notice',
			'customer_processing_order',
			array( 'order' => 99 )
		);
	}

	/**
	 * @testdox Skipped log includes object context for WC_Order.
	 */
	public function test_skipped_log_includes_order_context(): void {
		$order = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 77 );
		$email = $this->create_mock_email( 'new_order', 'admin@example.com', $order );

		$this->sut->handle_woocommerce_email_skipped( \WC_Email::SKIP_REASON_NO_RECIPIENT, 'new_order', $email );

		$this->assertLogged(
			'notice',
			'new_order',
			array( 'order' => 77 )
		);
	}

	/**
	 * @testdox send_notification() fires woocommerce_email_disabled and returns false when email is disabled.
	 */
	public function test_send_notification_fires_disabled_and_returns_false_when_disabled(): void {
		$email = $this->create_testable_email( 'my_email', '', false );

		$disabled_fired = false;
		add_action(
			'woocommerce_email_disabled',
			function ( $email_id ) use ( &$disabled_fired ) {
				if ( 'my_email' === $email_id ) {
					$disabled_fired = true;
				}
			}
		);

		$result = $email->run_send_notification();

		$this->assertFalse( $result, 'send_notification() should return false when email is disabled' );
		$this->assertTrue( $disabled_fired, 'woocommerce_email_disabled should fire when email is disabled' );
		$this->assertFalse( $email->send_called, 'send() should not be called when email is disabled' );
	}

	/**
	 * @testdox send_notification() fires woocommerce_email_skipped with no_recipient and returns false when recipient is empty.
	 */
	public function test_send_notification_fires_skipped_and_returns_false_when_no_recipient(): void {
		$email = $this->create_testable_email( 'my_email', '', true );

		$skipped_reason = null;
		add_action(
			'woocommerce_email_skipped',
			function ( $reason, $email_id ) use ( &$skipped_reason ) {
				if ( 'my_email' === $email_id ) {
					$skipped_reason = $reason;
				}
			},
			10,
			2
		);

		$result = $email->run_send_notification();

		$this->assertFalse( $result, 'send_notification() should return false when no recipient' );
		$this->assertSame( \WC_Email::SKIP_REASON_NO_RECIPIENT, $skipped_reason, 'woocommerce_email_skipped should fire with no_recipient reason' );
		$this->assertFalse( $email->send_called, 'send() should not be called when no recipient' );
	}

	/**
	 * @testdox send_notification() calls send() with the correct arguments and forwards its return value when enabled and recipient exists.
	 */
	public function test_send_notification_calls_send_and_returns_result_when_conditions_met(): void {
		$email = $this->create_testable_email( 'my_email', 'admin@example.com', true, true );

		$result = $email->run_send_notification();

		$this->assertTrue( $result, 'send_notification() should forward the return value from send()' );
		$this->assertTrue( $email->send_called, 'send() should be called when email is enabled and has a recipient' );
		$this->assertSame( 'admin@example.com', $email->send_args[0], 'send() should receive the cached recipient as first argument' );
	}

	/**
	 * @testdox send_if_recipient() fires woocommerce_email_skipped and returns false when recipient is empty.
	 */
	public function test_send_if_recipient_fires_skipped_and_returns_false_when_no_recipient(): void {
		$email = $this->create_testable_email( 'my_email', '', false );

		$skipped_fired = false;
		add_action(
			'woocommerce_email_skipped',
			function ( $reason, $email_id ) use ( &$skipped_fired ) {
				if ( 'my_email' === $email_id && \WC_Email::SKIP_REASON_NO_RECIPIENT === $reason ) {
					$skipped_fired = true;
				}
			},
			10,
			2
		);

		$result = $email->run_send_if_recipient();

		$this->assertFalse( $result, 'send_if_recipient() should return false when no recipient' );
		$this->assertTrue( $skipped_fired, 'woocommerce_email_skipped should fire with no_recipient reason' );
		$this->assertFalse( $email->send_called, 'send() should not be called when no recipient' );
	}

	/**
	 * @testdox send_if_recipient() calls send() even when is_enabled() is false, bypassing the enabled check.
	 */
	public function test_send_if_recipient_calls_send_even_when_disabled(): void {
		$email = $this->create_testable_email( 'my_email', 'admin@example.com', false, true );

		$result = $email->run_send_if_recipient();

		$this->assertTrue( $result, 'send_if_recipient() should forward the return value from send()' );
		$this->assertTrue( $email->send_called, 'send() should be called regardless of is_enabled() state' );
	}

	/**
	 * Create a minimal WC_Email subclass for unit-testing send_notification() and send_if_recipient().
	 *
	 * Exposes both protected helpers as public `run_*` wrappers and records whether send() was called.
	 *
	 * @param string $email_id       Email type ID.
	 * @param string $recipient      Recipient email address (empty string = no recipient).
	 * @param bool   $is_enabled     Return value for is_enabled().
	 * @param bool   $send_return    Return value for the stubbed send() (ignored if $use_real_send is true).
	 * @param bool   $use_real_send  If true, keep the real WC_Email::send() instead of stubbing it.
	 * @return object Anonymous class instance with `run_send_notification()`, `run_send_if_recipient()`,
	 *                `send_called`, and `send_args` properties.
	 */
	private function create_testable_email( string $email_id, string $recipient, bool $is_enabled, bool $send_return = false, bool $use_real_send = false ): object {
		return new class( $email_id, $recipient, $is_enabled, $send_return, $use_real_send ) extends \WC_Email {
			/** @var bool Whether send() has been invoked. */
			public bool $send_called = false;
			/** @var array Arguments captured from the most recent send() call. */
			public array $send_args = array();

			/** @var string Recipient returned by get_recipient(). */
			private string $test_recipient;
			/** @var bool Value returned by is_enabled(). */
			private bool $test_is_enabled;
			/** @var bool Value returned by send(). */
			private bool $test_send_return;
			/** @var bool Whether to delegate to the real WC_Email::send() instead of returning $test_send_return. */
			private bool $use_real_send;

			/**
			 * Construct the test double.
			 *
			 * @param string $email_id    The email type ID to expose on the instance.
			 * @param string $recipient   Recipient string for get_recipient().
			 * @param bool   $is_enabled  Value to return from is_enabled().
			 * @param bool   $send_return Value to return from send().
			 * @param bool   $use_real_send Whether to delegate to the real send() instead of the stub.
			 */
			public function __construct( string $email_id, string $recipient, bool $is_enabled, bool $send_return, bool $use_real_send ) {
				// Deliberately skip parent::__construct() to avoid side-effects in tests.
				$this->id               = $email_id;
				$this->test_recipient   = $recipient;
				$this->test_is_enabled  = $is_enabled;
				$this->test_send_return = $send_return;
				$this->use_real_send    = $use_real_send;
			}

			/**
			 * @return bool Configured is_enabled() return value.
			 */
			public function is_enabled(): bool {
				return $this->test_is_enabled;
			}

			/**
			 * @return string Configured recipient string.
			 */
			public function get_recipient(): string {
				return $this->test_recipient;
			}

			/**
			 * @return string Static test subject.
			 */
			public function get_subject(): string {
				return 'Test subject';
			}

			/**
			 * @return string Static test content.
			 */
			public function get_content(): string {
				return 'Test content';
			}

			/**
			 * @return string Empty headers string.
			 */
			public function get_headers(): string {
				return '';
			}

			/**
			 * @return array Empty attachments array.
			 */
			public function get_attachments(): array {
				return array();
			}

			/**
			 * Record the send() invocation and return the configured result.
			 *
			 * @param string $to          Recipient.
			 * @param string $subject     Subject.
			 * @param string $message     Body.
			 * @param string $headers     Headers.
			 * @param array  $attachments Attachments.
			 * @return bool Configured send() return value.
			 */
			public function send( $to, $subject, $message, $headers, $attachments ): bool {
				$this->send_called = true;
				$this->send_args   = array( $to, $subject, $message, $headers, $attachments );

				if ( $this->use_real_send ) {
					return parent::send( $to, $subject, $message, $headers, $attachments );
				}

				return $this->test_send_return;
			}

			/** Exposes the protected send_notification() for testing. */
			public function run_send_notification(): bool {
				return $this->send_notification();
			}

			/** Exposes the protected send_if_recipient() for testing. */
			public function run_send_if_recipient(): bool {
				return $this->send_if_recipient();
			}
		};
	}

	/**
	 * Create a mock WC_Email object for testing.
	 *
	 * @param string $email_id  Email type ID.
	 * @param string $recipient Recipient email address.
	 * @param mixed  $wc_object Related WooCommerce object or false.
	 * @return \WC_Email
	 */
	private function create_mock_email( string $email_id, string $recipient, $wc_object = false ): \WC_Email {
		$email         = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$email->id     = $email_id;
		$email->object = $wc_object;
		$email->expects( $this->any() )->method( 'get_recipient' )->willReturn( $recipient );
		$email->expects( $this->any() )->method( 'get_title' )->willReturn( $email_id );

		return $email;
	}

	/**
	 * @testdox send() coerces a non-bool return from the mail callback into a bool.
	 */
	public function test_send_coerces_non_bool_mail_callback_return_to_bool(): void {
		add_filter(
			'woocommerce_mail_callback',
			function () {
					return static function () {
							return null;
					};
			}
		);

		$email  = new \WC_Email();
		$result = $email->send( 'test@example.com', 'Subject', 'Message', '', array() );

		$this->assertIsBool( $result, 'send() should always return a bool, even if the mail callback does not' );
		$this->assertFalse( $result, 'A non-bool (null) callback return should coerce to false' );
	}

	/**
	 * @testdox send_notification() does not throw a TypeError when the mail callback returns a non-bool.
	 */
	public function test_send_notification_does_not_fatal_when_mail_callback_returns_non_bool(): void {
		add_filter(
			'woocommerce_mail_callback',
			function () {
					return static function () {
							return null;
					};
			}
		);

		$email = $this->create_testable_email( 'my_email', 'admin@example.com', true, false, true );

		$result = $email->run_send_notification();

		$this->assertIsBool( $result, 'send_notification() should return a bool instead of fataling' );
		$this->assertFalse( $result, 'Should resolve to false when the underlying mail callback returns null' );
	}

	/**
	 * @testdox send() casts the mail callback return to bool before firing woocommerce_email_sent.
	 */
	public function test_send_fires_email_sent_action_with_bool_when_callback_returns_non_bool(): void {
		add_filter(
			'woocommerce_mail_callback',
			function () {
					return static function () {
							return null;
					};
			}
		);

		$received_return = null;
		add_action(
			'woocommerce_email_sent',
			function ( $result ) use ( &$received_return ) {
				$received_return = $result;
			},
			10,
			1
		);

		$email = new \WC_Email();
		$email->send( 'test@example.com', 'Subject', 'Message', '', array() );

		$this->assertIsBool( $received_return, 'woocommerce_email_sent should receive a bool even when the mail callback returns null' );
		$this->assertFalse( $received_return );

		remove_all_actions( 'woocommerce_email_sent' );
	}
}
