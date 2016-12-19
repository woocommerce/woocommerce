<?php

/**
 * Class WC_Tests_Log_Handler_DB
 * @package WooCommerce\Tests\Log
 * @since 2.7.0
 */
class WC_Tests_Log_Handler_DB extends WC_Unit_Test_Case {

	public function tearDown() {
		WC_Log_Handler_DB::flush();
		parent::tearDown();
	}

	/**
	 * Test handle writes to database correctly.
	 *
	 * @since 2.7.0
	 */
	public function test_handle() {
		global $wpdb;

		$handler = new WC_Log_Handler_DB( array( 'threshold' => 'debug' ) );
		$time = time();
		$context = array( 1, 2, 'a', 'b', 'key' => 'value' );

		$handler->handle( $time, 'debug', 'msg_debug', array( 'tag' => 'tag_debug' ) );
		$handler->handle( $time, 'info', 'msg_info', array( 'tag' => 'tag_info' ) );
		$handler->handle( $time, 'notice', 'msg_notice', array( 'tag' => 'tag_notice' ) );
		$handler->handle( $time, 'warning', 'msg_warning', array( 'tag' => 'tag_warning' ) );
		$handler->handle( $time, 'error', 'msg_error', array( 'tag' => 'tag_error' ) );
		$handler->handle( $time, 'critical', 'msg_critical', array( 'tag' => 'tag_critical' ) );
		$handler->handle( $time, 'alert', 'msg_alert', array( 'tag' => 'tag_alert' ) );
		$handler->handle( $time, 'emergency', 'msg_emergency', array( 'tag' => 'tag_emergency' ) );

		$handler->handle( $time, 'debug', 'context_test', $context );

		$log_entries = $wpdb->get_results( "SELECT timestamp, level, message, tag, context FROM {$wpdb->prefix}woocommerce_log", ARRAY_A );

		$expected_ts = date( 'Y-m-d H:i:s', $time );
		$expected = array(
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'debug' ),
				'message' => 'msg_debug',
				'tag' => 'tag_debug',
				'context' => serialize( array( 'tag' => 'tag_debug' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'info' ),
				'message' => 'msg_info',
				'tag' => 'tag_info',
				'context' => serialize( array( 'tag' => 'tag_info' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'notice' ),
				'message' => 'msg_notice',
				'tag' => 'tag_notice',
				'context' => serialize( array( 'tag' => 'tag_notice' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'warning' ),
				'message' => 'msg_warning',
				'tag' => 'tag_warning',
				'context' => serialize( array( 'tag' => 'tag_warning' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'error' ),
				'message' => 'msg_error',
				'tag' => 'tag_error',
				'context' => serialize( array( 'tag' => 'tag_error' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'critical' ),
				'message' => 'msg_critical',
				'tag' => 'tag_critical',
				'context' => serialize( array( 'tag' => 'tag_critical' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'alert' ),
				'message' => 'msg_alert',
				'tag' => 'tag_alert',
				'context' => serialize( array( 'tag' => 'tag_alert' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'emergency' ),
				'message' => 'msg_emergency',
				'tag' => 'tag_emergency',
				'context' => serialize( array( 'tag' => 'tag_emergency' ) ),
			),
			array(
				'timestamp' => $expected_ts,
				'level' => WC_Log_Levels::get_level_severity( 'debug' ),
				'message' => 'context_test',
				'tag' => '',
				'context' => serialize( $context ),
			),
		);

		$this->assertEquals( $log_entries, $expected );
	}


	/**
	 * Test flush.
	 *
	 * @since 2.7.0
	 */
	public function test_flush() {
		global $wpdb;

		$handler = new WC_Log_Handler_DB( array( 'threshold' => 'debug' ) );
		$time = time();

		$handler->handle( $time, 'debug', '', array() );

		$log_entries = $wpdb->get_results( "SELECT timestamp, level, message, tag FROM {$wpdb->prefix}woocommerce_log" );
		$this->assertCount( 1, $log_entries );

		WC_Log_Handler_DB::flush();

		$log_entries = $wpdb->get_results( "SELECT timestamp, level, message, tag FROM {$wpdb->prefix}woocommerce_log" );
		$this->assertCount( 0, $log_entries );
	}

}
