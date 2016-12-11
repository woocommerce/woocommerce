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
	 * Test handle writes to database correctly.
	 *
	 * @since 2.8
	 */
	public function test_handle() {
		$handler = new WC_Log_Handler_Email();
		$time = time();

		$handler->handle( $time, 'emergency', 'msg_emergency', array() );

		$mailer = tests_retrieve_phpmailer_instance();

		$this->assertEquals(
			'You have received the following WooCommerce log message:' . PHP_EOL . PHP_EOL . date( 'c', $time ) . ' EMERGENCY msg_emergency' . PHP_EOL,
			$mailer->get_sent()->body
		);
	}

}
