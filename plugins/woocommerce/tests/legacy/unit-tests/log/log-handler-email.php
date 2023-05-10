<?php
/**
 * Class WC_Tests_Log_Handler_Email file.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WC_Tests_Log_Handler_Email
 * @package WooCommerce\Tests\Log
 * @since 3.0.0
 */
class WC_Tests_Log_Handler_Email extends WC_Unit_Test_Case {

	/**
	 * Test setup.
	 */
	public function setUp(): void {
		parent::setUp();
		reset_phpmailer_instance();
		add_filter(
			'wp_mail_from',
			array( $this, 'filter_test_email' )
		);
	}

	/**
	 * Test teardown.
	 */
	public function tearDown(): void {
		remove_filter(
			'wp_mail_from',
			array( $this, 'filter_test_email' )
		);
		reset_phpmailer_instance();
		parent::tearDown();
	}

	/**
	 * Test handle sends email correctly.
	 *
	 * @since 3.0.0
	 */
	public function test_handle() {
		$mailer    = tests_retrieve_phpmailer_instance();
		$time      = time();
		$site_name = get_bloginfo( 'name' );

		$handler = new WC_Log_Handler_Email();
		$handler->handle( $time, 'emergency', 'msg_emergency', array() );
		$handler->handle( $time, 'emergency', 'msg_emergency 2', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );
		$this->assertEquals(
			(
				'You have received the following WooCommerce log messages:'
				. PHP_EOL
				. PHP_EOL
				. gmdate( 'c', $time ) . ' EMERGENCY msg_emergency'
				. PHP_EOL
				. gmdate( 'c', $time ) . ' EMERGENCY msg_emergency 2'
				. PHP_EOL
				. PHP_EOL
				. "Visit {$site_name} admin area:"
				. PHP_EOL
				. admin_url()
				. PHP_EOL
			),
			$this->normalize_eol( $mailer->get_sent( 0 )->body )
		);
		$this->assertEquals(
			"[{$site_name}] EMERGENCY: 2 WooCommerce log messages",
			$mailer->get_sent( 0 )->subject
		);
		$this->assertEquals( get_option( 'admin_email' ), $mailer->get_recipient( 'to' )->address );

		$handler->handle( $time, 'emergency', 'msg_emergency', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );
		$this->assertEquals(
			(
				'You have received the following WooCommerce log message:'
				. PHP_EOL
				. PHP_EOL
				. gmdate( 'c', $time ) . ' EMERGENCY msg_emergency'
				. PHP_EOL
				. PHP_EOL
				. "Visit {$site_name} admin area:"
				. PHP_EOL
				. admin_url()
				. PHP_EOL
			),
			$this->normalize_eol( $mailer->get_sent( 1 )->body )
		);
	}

	/**
	 * Replace network-style end of lines with PHP-style end of lines in a string.
	 *
	 * @param string $string The string to do the replacements in.
	 * @return string The string once the replacement has been done.
	 */
	private function normalize_eol( $string ) {
		return str_replace( "\r\n", PHP_EOL, $string );
	}

	/**
	 * Test email subject
	 *
	 * @since 3.0.0
	 */
	public function test_email_subject() {
		$mailer    = tests_retrieve_phpmailer_instance();
		$time      = time();
		$site_name = get_bloginfo( 'name' );

		$handler = new WC_Log_Handler_Email( null, WC_Log_Levels::DEBUG );
		$handler->handle( $time, 'debug', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );

		$handler->handle( $time, 'alert', '', array() );
		$handler->handle( $time, 'critical', '', array() );
		$handler->handle( $time, 'debug', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );

		$this->assertEquals(
			"[{$site_name}] DEBUG: 1 WooCommerce log message",
			$mailer->get_sent( 0 )->subject
		);

		$this->assertEquals(
			"[{$site_name}] ALERT: 3 WooCommerce log messages",
			$mailer->get_sent( 1 )->subject
		);
	}

	/**
	 * Test multiple recipients receive emails.
	 *
	 * @since 3.0.0
	 */
	public function test_multiple_recipients() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email(
			array(
				'first@test.com',
				'Second Recipient <second@test.com>',
			)
		);
		$handler->handle( time(), 'emergency', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );

		$first_recipient  = $mailer->get_recipient( 'to', 0, 0 );
		$second_recipient = $mailer->get_recipient( 'to', 0, 1 );

		$this->assertEquals( 'first@test.com', $first_recipient->address );
		$this->assertEquals( 'second@test.com', $second_recipient->address );
		$this->assertEquals( 'Second Recipient', $second_recipient->name );
	}

	/**
	 * Test single recipient receives emails.
	 *
	 * @since 3.0.0
	 */
	public function test_single_recipient() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( 'User <user@test.com>' );
		$handler->handle( time(), 'emergency', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );

		$recipient = $mailer->get_recipient( 'to' );
		$this->assertEquals( 'user@test.com', $recipient->address );
		$this->assertEquals( 'User', $recipient->name );
	}

	/**
	 * Test threshold.
	 *
	 * @since 3.0.0
	 */
	public function test_threshold() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( null, 'notice' );
		$handler->handle( time(), 'info', '', array() );
		$result = $handler->send_log_email();

		// Info should not be handled, get_sent is false.
		$this->assertFalse( $result );
		$this->assertFalse( $mailer->get_sent( 0 ) );

		$handler->handle( time(), 'notice', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );
		$this->assertTrue( property_exists( $mailer->get_sent( 0 ), 'body' ) );
	}

	/**
	 * Test set_threshold().
	 *
	 * @since 3.0.0
	 */
	public function test_set_threshold() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( null, 'notice' );
		$handler->handle( time(), 'info', '', array() );
		$result = $handler->send_log_email();

		// Info should not be handled, get_sent is false.
		$this->assertFalse( $result );
		$this->assertFalse( $mailer->get_sent( 0 ) );

		$handler->set_threshold( 'info' );
		$handler->handle( time(), 'info', '', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );
		$this->assertTrue( property_exists( $mailer->get_sent( 0 ), 'body' ) );
	}

	/**
	 * Test send_log_email clears logs.
	 *
	 * Send log email could be called multiple times during a request. The same log should not be
	 * sent multiple times.
	 *
	 * @since 3.0.0
	 */
	public function test_multiple_send_log() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email();
		$time    = time();
		$handler->handle( $time, 'emergency', 'message 1', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );
		$handler->handle( $time, 'emergency', 'message 2', array() );
		$result = $handler->send_log_email();

		$this->assertTrue( $result );

		$site_name = get_bloginfo( 'name' );

		$this->assertEquals(
			(
				'You have received the following WooCommerce log message:'
				. PHP_EOL
				. PHP_EOL
				. gmdate( 'c', $time ) . ' EMERGENCY message 1'
				. PHP_EOL
				. PHP_EOL
				. "Visit {$site_name} admin area:"
				. PHP_EOL
				. admin_url()
				. PHP_EOL
			),
			$this->normalize_eol( $mailer->get_sent( 0 )->body )
		);

		$this->assertEquals(
			(
				'You have received the following WooCommerce log message:'
				. PHP_EOL
				. PHP_EOL
				. gmdate( 'c', $time ) . ' EMERGENCY message 2'
				. PHP_EOL
				. PHP_EOL
				. "Visit {$site_name} admin area:"
				. PHP_EOL
				. admin_url()
				. PHP_EOL
			),
			$this->normalize_eol( $mailer->get_sent( 1 )->body )
		);
	}

	/**
	 * Filters the "from" address in emails to use the expected test email.
	 *
	 * @return string
	 */
	public function filter_test_email() {
		return WP_TESTS_EMAIL;
	}
}
