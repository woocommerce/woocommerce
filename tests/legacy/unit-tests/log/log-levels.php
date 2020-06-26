<?php

/**
 * Class WC_Tests_Logger
 * @package WooCommerce\Tests\Log
 * @since 3.0.0
 */
class WC_Tests_Log_Levels extends WC_Unit_Test_Case {

	/**
	 * Test get_level_severity().
	 *
	 * @since 3.0.0
	 */
	public function test_get_level_severity() {
		$this->assertEquals( 0, WC_Log_Levels::get_level_severity( 'unrecognized level' ) );
		$this->assertEquals( 100, WC_Log_Levels::get_level_severity( 'debug' ) );
		$this->assertEquals( 200, WC_Log_Levels::get_level_severity( 'info' ) );
		$this->assertEquals( 300, WC_Log_Levels::get_level_severity( 'notice' ) );
		$this->assertEquals( 400, WC_Log_Levels::get_level_severity( 'warning' ) );
		$this->assertEquals( 500, WC_Log_Levels::get_level_severity( 'error' ) );
		$this->assertEquals( 600, WC_Log_Levels::get_level_severity( 'critical' ) );
		$this->assertEquals( 700, WC_Log_Levels::get_level_severity( 'alert' ) );
		$this->assertEquals( 800, WC_Log_Levels::get_level_severity( 'emergency' ) );
	}

	/**
	 * Test get_severity_level().
	 *
	 * @since 3.0.0
	 */
	public function test_get_severity_level() {
		$this->assertFalse( WC_Log_Levels::get_severity_level( 0 ) );
		$this->assertEquals( 'debug', WC_Log_Levels::get_severity_level( 100 ) );
		$this->assertEquals( 'info', WC_Log_Levels::get_severity_level( 200 ) );
		$this->assertEquals( 'notice', WC_Log_Levels::get_severity_level( 300 ) );
		$this->assertEquals( 'warning', WC_Log_Levels::get_severity_level( 400 ) );
		$this->assertEquals( 'error', WC_Log_Levels::get_severity_level( 500 ) );
		$this->assertEquals( 'critical', WC_Log_Levels::get_severity_level( 600 ) );
		$this->assertEquals( 'alert', WC_Log_Levels::get_severity_level( 700 ) );
		$this->assertEquals( 'emergency', WC_Log_Levels::get_severity_level( 800 ) );
	}


	/**
	 * Test is_valid_level().
	 *
	 * @since 3.0.0
	 */
	public function test_is_valid_level() {
		$this->assertFalse( WC_Log_Levels::is_valid_level( 'unrecognized level' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'debug' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'info' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'notice' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'warning' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'error' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'critical' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'alert' ) );
		$this->assertTrue( WC_Log_Levels::is_valid_level( 'emergency' ) );
	}

}
