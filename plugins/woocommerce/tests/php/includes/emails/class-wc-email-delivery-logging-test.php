<?php
/**
 * Tests that failed transactional email sends are logged and opted into remote logging.
 *
 * @package WooCommerce\Tests\Emails
 */

declare( strict_types = 1 );

/**
 * Recording logger that captures error() calls for assertions.
 *
 * Implements WC_Logger_Interface directly (rather than extending WC_Logger) so that
 * once the `woocommerce_logging_class` filter is removed, wc_get_logger() re-resolves
 * a real WC_Logger and this recorder does not leak into other tests.
 */
class WC_Email_Delivery_Recording_Logger implements WC_Logger_Interface {
	/**
	 * Captured error records: each is array( 'message' => string, 'context' => array ).
	 *
	 * @var array
	 */
	public static $errors = array();

	/**
	 * Record error-level logs.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 */
	public function error( $message, $context = array() ) {
		self::$errors[] = array(
			'message' => $message,
			'context' => $context,
		);
	}

	// Unused interface methods — no-ops for this test double.
	public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {}
	public function log( $level, $message, $context = array() ) {}
	public function emergency( $message, $context = array() ) {}
	public function alert( $message, $context = array() ) {}
	public function critical( $message, $context = array() ) {}
	public function warning( $message, $context = array() ) {}
	public function notice( $message, $context = array() ) {}
	public function info( $message, $context = array() ) {}
	public function debug( $message, $context = array() ) {}
}

/**
 * Class WC_Email_Delivery_Logging_Test
 */
class WC_Email_Delivery_Logging_Test extends \WC_Unit_Test_Case {

	/**
	 * Route wc_get_logger() to the recording logger and reset captures.
	 */
	public function setUp(): void {
		parent::setUp();
		WC_Email_Delivery_Recording_Logger::$errors = array();
		add_filter( 'woocommerce_logging_class', array( $this, 'use_recording_logger' ) );
	}

	/**
	 * Remove filters so the swapped logger does not leak into other tests.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_logging_class', array( $this, 'use_recording_logger' ) );
		remove_all_filters( 'woocommerce_mail_callback' );
		parent::tearDown();
	}

	/**
	 * Filter callback: swap the logger class.
	 *
	 * @return string
	 */
	public function use_recording_logger() {
		return WC_Email_Delivery_Recording_Logger::class;
	}

	/**
	 * Filter callback: force wp_mail to report failure.
	 *
	 * @return callable
	 */
	public function fail_mail_callback() {
		return '__return_false';
	}

	/**
	 * Filter callback: force wp_mail to report success.
	 *
	 * @return callable
	 */
	public function succeed_mail_callback() {
		return '__return_true';
	}

	/**
	 * Collect captured logs whose source is the email-delivery channel.
	 *
	 * @return array
	 */
	private function delivery_failure_logs(): array {
		return array_values(
			array_filter(
				WC_Email_Delivery_Recording_Logger::$errors,
				function ( $record ) {
					return isset( $record['context']['source'] ) && 'wc-email-delivery' === $record['context']['source'];
				}
			)
		);
	}

	/**
	 * A failed send emits exactly one error log opted into remote logging, without the recipient.
	 */
	public function test_failed_send_is_logged_for_remote_logging() {
		add_filter( 'woocommerce_mail_callback', array( $this, 'fail_mail_callback' ) );

		$email     = new WC_Email();
		$email->id = 'customer_completed_order';
		$email->send( 'customer@example.com', 'Your order', '<p>Body</p>', '', array() );

		$logs = $this->delivery_failure_logs();
		$this->assertCount( 1, $logs, 'A failed send should emit exactly one wc-email-delivery error log.' );
		$this->assertTrue( $logs[0]['context']['remote-logging'], 'The failure log must opt into remote logging.' );
		$this->assertStringContainsString( 'customer_completed_order', $logs[0]['message'], 'The log should identify the email id.' );
		$this->assertStringNotContainsString( 'customer@example.com', $logs[0]['message'], 'The recipient address (PII) must not be logged.' );
	}

	/**
	 * A successful send emits no email-delivery error log.
	 */
	public function test_successful_send_is_not_logged() {
		add_filter( 'woocommerce_mail_callback', array( $this, 'succeed_mail_callback' ) );

		$email     = new WC_Email();
		$email->id = 'customer_completed_order';
		$email->send( 'customer@example.com', 'Your order', '<p>Body</p>', '', array() );

		$this->assertCount( 0, $this->delivery_failure_logs(), 'A successful send must not emit a wc-email-delivery error log.' );
	}
}
