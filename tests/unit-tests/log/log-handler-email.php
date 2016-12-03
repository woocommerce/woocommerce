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
		$handler = new WC_Log_Handler_Email( array( 'threshold' => 'debug' ) );
		$time = time();

		$handler->handle( $time, 'debug', 'msg_debug', array() );

		$mailer = tests_retrieve_phpmailer_instance();

		$this->assertEquals(
			'You have recieved the following WooCommerce log message:' . PHP_EOL . PHP_EOL . date( 'c', $time ) . ' DEBUG msg_debug' . PHP_EOL,
			$mailer->get_sent()->body
		);
	}

}
