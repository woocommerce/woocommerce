<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\CycleStateService;

/**
 * CycleStateServiceTests data tests.
 */
class CycleStateServiceTests extends \WC_Unit_Test_Case {

	/**
	 * @var CycleStateService
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CycleStateService();
	}

	/**
	 * Test get_option_name method.
	 */
	public function test_get_option_name() {
		$product_id = 123;
		$method     = $this->get_private_method( $this->sut, 'get_option_name' );
		$result     = $method->invokeArgs( $this->sut, array( $product_id ) );
		$this->assertEquals( CycleStateService::STATE_OPTION_PREFIX . $product_id, $result );
	}

	/**
	 * Test get_option_name method with a product ID of 0.
	 */
	public function test_get_option_name_with_product_id_0() {
		$product_id = 0;
		$method     = $this->get_private_method( $this->sut, 'get_option_name' );
		$result     = $method->invokeArgs( $this->sut, array( $product_id ) );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test get_raw_cycle_state method.
	 */
	public function test_get_raw_cycle_state() {
		$product_id = 123;
		$method     = $this->get_private_method( $this->sut, 'get_raw_cycle_state' );
		$result     = $method->invokeArgs( $this->sut, array( $product_id ) );
		$this->assertEmpty( $result );

		$cycle_state = array(
			'cycle_start_time' => time(),
			'total_count'      => 10,
			'sent_count'       => 5,
			'failed_count'     => 2,
			'skipped_count'    => 3,
			'duration'         => 100,
		);
		update_option( CycleStateService::STATE_OPTION_PREFIX . $product_id, $cycle_state, false );
		$result = $method->invokeArgs( $this->sut, array( $product_id ) );
		$this->assertEquals( $cycle_state, $result );
	}

	/**
	 * Test get_or_initialize_cycle_state method.
	 */
	public function test_get_or_initialize_cycle_state() {
		$product_id = 123;
		$result     = $this->sut->get_or_initialize_cycle_state( $product_id );
		$this->assertArrayHasKey( 'cycle_start_time', $result );
		$this->assertArrayHasKey( 'total_count', $result );
		$this->assertArrayHasKey( 'sent_count', $result );
		$this->assertArrayHasKey( 'failed_count', $result );
		$this->assertArrayHasKey( 'skipped_count', $result );
		$this->assertArrayHasKey( 'duration', $result );
	}

	/**
	 * Test get_or_initialize_cycle_state method with a product ID of 0.
	 */
	public function test_get_or_initialize_cycle_state_with_product_id_0() {
		$product_id = 0;
		$this->expectException( \Exception::class );
		$this->sut->get_or_initialize_cycle_state( $product_id );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product_id ) );
	}

	/**
	 * Test get_or_initialize_cycle_state method with invalid state.
	 */
	public function test_get_or_initialize_cycle_state_with_invalid_state() {
		$product_id  = 123;
		$cycle_state = array(
			'cycle_start_time' => time(),
		);
		$this->sut->save_cycle_state( $product_id, $cycle_state );
		$this->expectException( \Exception::class );
		$this->sut->get_or_initialize_cycle_state( $product_id );
	}

	/**
	 * Test get_or_initialize_cycle_state method with invalid start timestamp.
	 */
	public function test_get_or_initialize_cycle_state_with_invalid_start_timestamp() {
		$product_id  = 123;
		$cycle_state = array(
			'cycle_start_time' => 'invalid',
			'total_count'      => 10,
			'sent_count'       => 5,
			'failed_count'     => 2,
			'skipped_count'    => 3,
			'duration'         => 100,
		);
		$this->sut->save_cycle_state( $product_id, $cycle_state );
		$this->expectException( \Exception::class );
		$this->sut->get_or_initialize_cycle_state( $product_id );
	}

	/**
	 * Test save_cycle_state method.
	 */
	public function test_save_cycle_state() {
		$product_id  = 123;
		$cycle_state = array(
			'cycle_start_time' => time(),
			'total_count'      => 10,
			'sent_count'       => 5,
			'failed_count'     => 2,
			'skipped_count'    => 3,
			'duration'         => 100,
		);
		$this->sut->save_cycle_state( $product_id, $cycle_state );
		$this->assertEquals( $cycle_state, get_option( CycleStateService::STATE_OPTION_PREFIX . $product_id ) );
	}

	/**
	 * Test save_cycle_state method with an empty cycle state.
	 */
	public function test_save_cycle_state_with_empty_cycle_state() {
		$product_id = 123;
		$this->sut->save_cycle_state( $product_id, array() );

		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product_id ) );
	}

	/**
	 * Test complete_cycle method.
	 */
	public function test_complete_cycle() {
		$product_id  = 123;
		$cycle_state = array(
			'cycle_start_time' => time(),
			'total_count'      => 10,
			'sent_count'       => 5,
			'failed_count'     => 2,
			'skipped_count'    => 3,
			'duration'         => 100,
		);
		$this->sut->complete_cycle( $product_id, $cycle_state );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product_id ) );
	}

	/**
	 * Get private method.
	 *
	 * @param object $instance The object.
	 * @param string $method_name The method name.
	 * @return \ReflectionMethod
	 */
	private function get_private_method( $instance, $method_name ) {
		$method = new \ReflectionMethod( $instance, $method_name );
		$method->setAccessible( true );
		return $method;
	}
}
