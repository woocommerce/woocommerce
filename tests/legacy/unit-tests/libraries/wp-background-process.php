<?php
/**
 * WP_Background_Processing Tests.
 *
 * @package WooCommerce\Tests\Libraries
 */

/**
 * Class Functions.
 *
 * @since 3.3
 */
class WC_Tests_Libraries_Background_Process extends WC_Unit_Test_Case {

	/**
	 * Test the is_queue_empty function.
	 *
	 * @return void
	 */
	public function test_is_queue_empty() {
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-wc-mock-background-process.php';
		$queue = new WC_Mock_Background_Process();
		$this->assertEquals( true, $queue->is_queue_empty() );
		$queue->push_to_queue(
			array(
				'mock_key' => 'mock_value',
			)
		);
		$queue->save();
		$this->assertEquals( false, $queue->is_queue_empty() );
	}

	/**
	 * Make sure the cron works.
	 *
	 * @return void
	 */
	public function test_schedule_cron_healthcheck() {
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-wc-mock-background-process.php';
		$queue = new WC_Mock_Background_Process();
		$this->assertArrayHasKey( 'wp_' . get_current_blog_id() . '_wc_mock_background_process_cron_interval', $queue->schedule_cron_healthcheck( array() ) );
	}
	/**
	 * Test prefix & action against identifier.
	 */
	public function test_identifier() {
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-wc-mock-background-process.php';
		$queue = new WC_Mock_Background_Process();
		$this->assertEquals( 'wp_' . get_current_blog_id() . '_wc_mock_background_process', $queue->get_identifier() );
	}

	/**
	 * Test to make sure a batch is returned.
	 *
	 * @return void
	 */
	public function test_get_batch() {
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-wc-mock-background-process.php';
		$queue = new WC_Mock_Background_Process();
		$queue->push_to_queue(
			array(
				'mock_key' => 'mock_value',
			)
		);
		$queue->save();
		$this->assertNotEmpty( $queue->get_batch() );
	}


}
