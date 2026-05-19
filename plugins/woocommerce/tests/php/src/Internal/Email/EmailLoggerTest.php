<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailLogger;
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
		$this->sut = new EmailLogger();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_email_log_enabled' );
		remove_all_filters( 'woocommerce_email_log_context' );
		remove_action( 'woocommerce_email_sent', array( $this->sut, 'handle_woocommerce_email_sent' ) );
		remove_action( 'wp_mail_failed', array( $this->sut, 'capture_mail_error' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Register method adds hooks for woocommerce_email_sent and wp_mail_failed.
	 */
	public function test_register_adds_hook(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_action( 'woocommerce_email_sent', array( $this->sut, 'handle_woocommerce_email_sent' ) ),
			'Expected hook to be registered for woocommerce_email_sent'
		);
		$this->assertNotFalse(
			has_action( 'wp_mail_failed', array( $this->sut, 'capture_mail_error' ) ),
			'Expected hook to be registered for wp_mail_failed'
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
		$this->assertStringNotContainsString( 'server.example.org', $log['message'], 'Domain-only host names should be left intact (only address-shaped tokens are redacted).' );
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

		return $email;
	}
}
