<?php

/**
 * Class WC_Tests_Log_Handler_Email
 * @package WooCommerce\Tests\Log
 * @since 2.8
 */
class WC_Tests_Log_Handler_Email extends WC_Unit_Test_Case {

	function setUp() {
		parent::setUp();
		reset_phpmailer_instance();
	}

	function tearDown() {
		reset_phpmailer_instance();
		parent::tearDown();
	}

	/**
	 * Test handle sends email correctly.
	 *
	 * @since 2.8
	 */
	public function test_handle() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email();
		$time = time();
		$handler->handle( $time, 'emergency', 'msg_emergency', array() );

		$site_name = get_bloginfo( 'name' );

		$this->assertEquals(
			'You have received the following WooCommerce log message:' . PHP_EOL . PHP_EOL . date( 'c', $time ) . ' EMERGENCY msg_emergency' . PHP_EOL,
			$mailer->get_sent()->body
		);
		$this->assertEquals(
			"[EMERGENCY] WooCommerce log message from {$site_name}",
			$mailer->get_sent()->subject
		);
		$this->assertEquals( get_option( 'admin_email' ), $mailer->get_recipient( 'to' )->address );
	}


	/**
	 * Test multiple recipients receive emails.
	 *
	 * @since 2.8
	 */
	public function test_multiple_recipients() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( array(
			'first@test.com',
			'Second Recipient <second@test.com>',
		) );
		$handler->handle( time(), 'emergency', '', array() );

		$first_recipient  = $mailer->get_recipient( 'to', 0, 0 );
		$second_recipient = $mailer->get_recipient( 'to', 0, 1 );

		$this->assertEquals( 'first@test.com', $first_recipient->address );
		$this->assertEquals( 'second@test.com', $second_recipient->address );
		$this->assertEquals( 'Second Recipient', $second_recipient->name );
	}

	/**
	 * Test single recipient receives emails.
	 *
	 * @since 2.8
	 */
	public function test_single_recipient() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( 'User <user@test.com>' );
		$handler->handle( time(), 'emergency', '', array() );

		$recipient  = $mailer->get_recipient( 'to' );
		$this->assertEquals( 'user@test.com', $recipient->address );
		$this->assertEquals( 'User', $recipient->name );
	}

	/**
	 * Test threshold.
	 *
	 * @since 2.8
	 */
	public function test_threshold() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( null, 'notice' );
		$handler->handle( time(), 'info', '', array() );

		// Info should not be handled, get_sent is false
		$this->assertFalse( $mailer->get_sent( 0 ) );

		$handler->handle( time(), 'notice', '', array() );
		$this->assertObjectHasAttribute( 'body', $mailer->get_sent( 0 ) );
	}

	/**
	 * Test set_threshold().
	 *
	 * @since 2.8
	 */
	public function test_set_threshold() {
		$mailer = tests_retrieve_phpmailer_instance();

		$handler = new WC_Log_Handler_Email( null, 'notice' );
		$handler->handle( time(), 'info', '', array() );

		// Info should not be handled, get_sent is false
		$this->assertFalse( $mailer->get_sent( 0 ) );

		$handler->set_threshold( 'info' );
		$handler->handle( time(), 'info', '', array() );
		$this->assertObjectHasAttribute( 'body', $mailer->get_sent( 0 ) );
	}
}
